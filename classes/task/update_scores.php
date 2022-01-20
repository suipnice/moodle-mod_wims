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
 * Defines the task which updates WIMS scores.
 *
 * @package   mod_wims
 * @copyright 2019 UCA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_wims\task;

use \mod_wims\wims_interface;

/**
 * The mod_wims updating scores task class
 *
 * @category  task
 * @package   mod_wims
 * @author    Badatos <bado@unice.fr>
 * @copyright 2019 UCA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://github.com/suipnice/moodle-mod_wims
 */
class update_scores extends \core\task\scheduled_task {
    /**
     * Get task name
     *
     * @return string the name of this task
     */
    public function get_name(): string {
        return get_string('updatescores', 'mod_wims');
    }

    /**
     * Execute task.
     *
     * @return void
     */
    public function execute(): void {
        global $CFG, $DB;

        include_once($CFG->libdir.'/gradelib.php');

        // Log a message and load up key utility classes.
        mtrace('Synchronising WIMS activity scores to grade book');
        include_once(__DIR__ . "/../../wimsinterface.class.php");
        $config = get_config('wims');
        $wims = new wims_interface($config, $config->debugcron, 'plain/text');

        // Build a lookup table to get Moodle user ids from wimslogin.
        $userlookup = $wims->builduserlookuptable();

        // Iterate over the set of WIMS activities in the system.
        $moduleinfo = $DB->get_record('modules', array('name' => 'wims'));
        $coursemodules = $DB->get_records('course_modules', array('module' => $moduleinfo->id), 'id', 'id,course,instance,section');

        foreach ($coursemodules as $cm) {
            mtrace(
                "\n------------\n- PROCESSING: course=".$cm->course.
                " section=".$cm->section.
                " cm=".$cm->id.
                " instance=".$cm->instance
            );

            // Make sure the course is correctly accessible.
            $isaccessible = $wims->verifyclassaccessible($cm);
            if (!$isaccessible) {
                mtrace('  - ALERT: Ignoring class as it is inaccessible - it may not have been setup yet');
                continue;
            }

            // Get the sheet index for this wims course.
            $sheetindex = $wims->getsheetindex($cm);
            if ($sheetindex == null) {
                mtrace('  ERROR: Failed to fetch sheet index for WIMS id: cm='.$cm->id);
                continue;
            }
            $requiredsheets = $wims->getrequiredsheets($sheetindex);

            // Fetch the scores for the required sheets.
            $sheetscores = $wims->getselectedscores($cm, $requiredsheets->ids);
            if ($sheetscores == null) {
                mtrace(' ERROR: Failed to fetch selected sheet scores for WIMS id: cm='.$cm->id);
                continue;
            }

            // We have an identifier problem: Exams and worksheets are both numbered from 1 up
            // and for scoring we need to have a unique identifier for each scoring column
            // so we're going to use an offset for worksheets.
            $offsetforsheettype = array('worksheets' => 1000, 'exams' => 0);

            $nbgradeitems = 0;
            $nbfailed = 0;
            // Iterate over the records to setup meta data - ie to assign sheet names to the correct score columns.
            foreach ($sheetscores as $sheettype => $sheets) {
                $itemnumberoffset = $offsetforsheettype[$sheettype];
                foreach ($sheets as $sheetid => $sheetdata) {
                    // Generate the key identifier that allows us to differentiate scores within a single exercise.
                    $itemnumber = $itemnumberoffset + $sheetid;
                    // Construct the grade column definition object (with the name of the exercise, score ranges, etc).
                    $sheettitle = $requiredsheets->titles[$sheettype][$sheetid];
                    // See {@link https://docs.moodle.org/dev/Grades#grade_items} for grade item props.
                    $params = array(
                        'itemname' => $sheettitle,
                        'grademin' => 0 ,
                        'grademax' => 10 );

                    // Apply the grade column definition.
                    $graderesult = grade_update('mod/wims', $cm->course, 'mod', 'wims', $cm->instance, $itemnumber, null, $params);
                    if ($graderesult != GRADE_UPDATE_OK) {
                        mtrace(
                            '  ERROR: Grade update failed to set meta data: '.
                            $sheettype.' '.$sheetid.
                            ' @ itemnumber = '.$itemnumber.' => '.$sheettitle
                        );
                        $nbfailed++;
                    } else {
                        $nbgradeitems++;
                    }
                }
            }
            mtrace("$nbgradeitems grade items updated ($nbfailed failed)");

            $nbgradeitems = 0;
            $nbfailed = 0;
            // Iterate over the sheet scores to write them to the database.
            foreach ($sheetscores as $sheettype => $sheets) {
                $itemnumberoffset = $offsetforsheettype[$sheettype];
                foreach ($sheets as $sheetid => $sheetdata) {
                    // Generate the key identifier that allows us to differentiate scores within a single exercise.
                    $itemnumber = $itemnumberoffset + $sheetid;
                    // Iterate over the per user records, updating the grade data for each.
                    foreach ($sheetdata as $username => $scorevalue) {
                        if (! array_key_exists($username, $userlookup)) {
                            mtrace(' ERROR: Failed to lookup WIMS login in MOODLE users for login: '.$username);
                            continue;
                        }
                        $userid = $userlookup[$username];
                        $grade = array(
                            'userid' => $userid,
                            'rawgrade' => $scorevalue);
                        $graderesult = grade_update(
                            'mod/wims',
                            $cm->course,
                            'mod',
                            'wims',
                            $cm->instance,
                            $itemnumber,
                            $grade,
                            null
                        );
                        if ($graderesult != GRADE_UPDATE_OK) {
                            mtrace(
                                ' ERROR: Grade update failed: '.
                                $sheettype.' '.$sheetid.': '.
                                $userid.' = '.$scorevalue.
                                ' @ itemnumber = '.$itemnumber
                            );
                            $nbfailed++;
                            continue;
                        } else {
                            $nbgradeitems++;
                        }
                    }
                }
            }
            mtrace($nbgradeitems.' user grade updated ($nbfailed failed)');
        }
        mtrace("\nSynchronising WIMS activity scores to grade book => Done.\n");

        /* return true; */
    }
}
