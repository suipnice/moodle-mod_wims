<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Defines the task which looks for H5P updates.
 *
 * @package    mod_wims
 * @copyright  2019 UCA
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_wims\task;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_wims updating scores task class
 *
 * @package    mod_wims
 * @copyright  2019 UCA
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_scores extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('updatescores', 'mod_wims');
    }

    public function execute() {
        global $CFG, $DB;

    require_once($CFG->libdir.'/gradelib.php');


    // log a message and load up key utility classes
    mtrace('Synchronising WIMS activity scores to grade book');
    require_once(__DIR__ . "/../../wimsinterface.class.php");
    $config=get_config('wims');
    $wims=new \wims_interface($config,$config->debugcron);

    // iterate over the set of WIMS activities in the system
    $moduleinfo = $DB->get_record('modules', array('name' => 'wims'));
    $coursemodules = $DB->get_records('course_modules', array('module' => $moduleinfo->id), 'id', 'id,course,instance,section');
    foreach($coursemodules as $cm){
        mtrace('- PROCESSING: course='.$cm->course.' section='.$cm->section.' cm='.$cm->id.' instance='.$cm->instance );

        // make sure the course is correctly accessible
        $isaccessible=$wims->verifyclassaccessible($cm);
        if (!$isaccessible){
            mtrace('  - ALERT: Ignoring class as it is inaccessible - it may not have been setup yet');
            continue;
        }

        // get the sheet index for this wims course
        $sheetindex=$wims->getsheetindex($cm);
        if ($sheetindex==null){
            mtrace('  ERROR: Failed to fetch sheet index for WIMS course: cm='.$cm->id );
            continue;
        }

        // iterate over the contents of the sheet index, storing pertinent entries in the 'required sheets' array
        $requiredsheets=array();
        $sheettitles=array();
        foreach ($sheetindex as $sheettype=>$sheets){
            $requiredsheets[$sheettype]=array();
            $sheettitles[$sheettype]=array();
            foreach ($sheets as $sheetid => $sheetsummary){
                // ignore sheets that are in preparation as WIMS complains if one tries to access their scores
                if ($sheetsummary->state==0){
                    mtrace('  - Ignoring: '.$sheettype.' '.$sheetid.': "'.$title.'" [state='.$sheetsummary->state.'] - due to STATE');
                    continue;
                }
                $title=$sheetsummary->title;
                // if the sheet name is tagged with a '*' then strip it off and process the sheet
                if (substr($title,-1)==='*'){
                    $title=trim(substr($title,0,-1));
                }else{
                    // we don't have a * so if we're not an exam then drop our
                    if ($sheettype!=='exams'){
                        mtrace('  - Ignoring: '.$sheettype.' '.$sheetid.': "'.$title.'" [state='.$sheetsummary->state.'] - due to Lack of *');
                        continue;
                    }
                }
                // we're ready to process the sheet
                mtrace('  * Keeping: '.$sheettype.' '.$sheetid.': "'.$title.'" [state='.$sheetsummary->state.']');
                $requiredsheets[$sheettype][]=$sheetid;
                $sheettitles[$sheettype][$sheetid]=$title;
            }
        }

        // fetch the scores for the required sheets
        $sheetscores=$wims->getsheetscores($cm,$requiredsheets);
        if ($sheetscores==null){
            mtrace('  ERROR: Failed to fetch sheet scores for WIMS course: cm='.$cm->id );
            continue;
        }

        // fetch the complete user list from moodle (and hope that we don't run out of RAM)
        $userrecords=$DB->get_records('user',null,'','id,firstname,lastname');

        // build a lookup table to get from user names to Moodle user ids
        $userlookup=array();
        foreach($userrecords as $userinfo){
            $wimslogin=$wims->generatewimslogin($userinfo);
            $userlookup[$wimslogin]=$userinfo->id;
        }

        // We have an identifier problem: Exams and worksheets are both numbered from 1 up
        // and for scoring we need to have a unique identifier for each scoring column
        // so we're going to use an offset for worksheets
        $itemnumberoffsetforsheettype = array( 'worksheets' => 1000, 'exams' => 0 );

        // iterate over the records to setup meta data - ie to assign sheet names to the correct score columns
        foreach ($sheetscores as $sheettype=>$sheets){
            $itemnumberoffset= $itemnumberoffsetforsheettype[$sheettype];
            foreach ($sheets as $sheetid => $sheetdata){
                // generate the key identifier that allows us to differentiate scores within a single exercise
                $itemnumber = $itemnumberoffset + $sheetid;
                // construct the grade column definition object (with the name of the exercise, score ranges, etc)
                $sheettitle = $sheettitles[$sheettype][$sheetid];
                $params = array( 'itemname' => $sheettitle );
                $params = array( 'grademin' => 0 );
                $params = array( 'grademax' => 10 );

                // apply the grade column definition
                $graderesult= grade_update('mod/wims', $cm->course, 'mod', 'wims', $cm->instance, $itemnumber, null, $params);
                if ($graderesult != GRADE_UPDATE_OK){
                    mtrace('  ERROR: Grade update failed to set meta data: '.$sheettype.' '.$sheetid.' @ itemnumber = '.$itemnumber.' => '.$sheettitle);
                }
            }
        }

        // iterate over the sheet scores to write them to the database
        foreach ($sheetscores as $sheettype=>$sheets){
            $itemnumberoffset= $itemnumberoffsetforsheettype[$sheettype];
            foreach ($sheets as $sheetid => $sheetdata){
                // generate the key identifier that allows us to differentiate scores within a single exercise
                $itemnumber = $itemnumberoffset + $sheetid;
                // iterate over the per user records, updating the grade data for each
                foreach ($sheetdata as $username => $scorevalue){
                    if (! array_key_exists($username,$userlookup)){
                        mtrace('  ERROR: Failed to lookup WIMS login in MOODLE users for login: '.$username);
                        continue;
                    }
                    $userid=$userlookup[$username];
                    $grade=array('userid'=>$userid,'rawgrade'=>$scorevalue);
                    $graderesult= grade_update('mod/wims', $cm->course, 'mod', 'wims', $cm->instance, $itemnumber, $grade, null);
                    if ($graderesult != GRADE_UPDATE_OK){
                        mtrace('  ERROR: Grade update failed: '.$sheettype.' '.$sheetid.': '.$userid.' = '.$scorevalue.' @ itemnumber = '.$itemnumber);
                        continue;
                    }
                }
            }
        }
    }
    mtrace('Synchronising WIMS activity scores to grade book => Done.');

    //return true;
    }
}
