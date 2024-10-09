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
 * Strings for component 'wims', language 'en'
 *
 * @package   mod_wims
 * @category  string
 * @copyright 2015 Edunao SAS <contact@edunao.com> / 2020 UniCA
 * @author    Sadge <daniel@edunao.com> / Badatos <bado@unice.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();


$string['admindescallowselfsigcerts'] = '';
$string['admindescdebugcron'] = '';
$string['admindescdebugsettings'] = '';
$string['admindescdebugviewpage'] = '';
$string['admindescdefaultinstitution'] = 'The establishment displayed in the WIMS classes';
$string['admindesclang'] = 'Must be one of ca, cn, en, es, fr, it, nl, si, tw, de';
$string['admindescserverpassword'] = 'The one you\'ve defined in the files in ".connexions" directory on WIMS server.';
$string['admindescserverurl'] = '';
$string['admindescusegradepage'] = '';
$string['admindescusenameinlogin'] = '';
$string['adminnameallowselfsigcerts'] = 'Allow self-signed certificates';
$string['adminnamedebugcron'] = 'CRON debug output';
$string['adminnamedebugsettings'] = 'SETTINGS debug output';
$string['adminnamedebugviewpage'] = 'VIEW debug output';
$string['adminnamedefaultinstitution'] = 'Institution Name (default value)';
$string['adminnamelang'] = 'Course Language (default value)';
$string['adminnameserverpassword'] = 'WIMS Server connection password';
$string['adminnameserverurl'] = 'WIMS Server URL';
$string['adminnameusegradepage'] = 'Redirect Moodle Gradebook links to WIMS grade page';
$string['adminnameusenameinlogin'] = 'Include user name in WIMS login';
$string['backup_found'] = 'There is a backup that can correspond to your class.';
$string['backup_help'] = 'WIMS automatically backs up before deleting a class. Choose the estimated deletion year.';
$string['backup_legend'] = 'Restore a previous backup';
$string['backup_restore'] = 'Restore';
$string['backup_select'] = 'Choose a backup file';
$string['backups_found'] = 'There are {$a} backups that can correspond to your class.';
$string['class_deleted'] = 'The WIMS class you\'re looking for doesn\'t exist anymore.';
$string['class_deleted_with_id'] = 'The WIMS class with id {$a} you\'re looking for doesn\'t exist anymore.';
$string['class_select_refused_desc'] = 'The WIMS class you are trying to reach does not allow access from this Moodle server. Access the class directly through WIMS or contact the site administrator.';
$string['class_select_refused_title'] = 'Access to WIMS class denied.';
$string['create_class_desc'] = 'Use the button below to create a new empty class.';
$string['create_new_class'] = 'Create a blank class';
$string['create_new_legend'] = 'Create a new class';
$string['error.class_select_failed_desc'] = 'The server is probably unavailable. Please retest in a few minutes, or notify the site administrator.';
$string['error.class_select_failed_title'] = 'Unable to access the WIMS classroom';
$string['grade__name'] = 'Item number 0 score';
$string['modulename'] = 'WIMS Course';
$string['modulename_help'] = 'Integrate a WIMS virtual classroom into your course';
$string['modulename_link'] = 'mod/wims/view';
$string['modulenameplural'] = 'WIMS courses';

$string['name'] = 'Activity Name';
$string['page-mod-wims-x'] = 'Any WIMS module page';
$string['pluginadministration'] = 'WIMS module administration';
$string['pluginname'] = 'WIMS';

$string['privacy:metadata:core_grades'] = 'The WIMS activity stores grades of users that have answered WIMS content.';
$string['privacy:metadata:wims'] = 'Informations on WIMS classroom';
$string['privacy:metadata:wims_classes:course'] = '';
$string['privacy:metadata:wims_classes:id'] = '';
$string['privacy:metadata:wims_classes:name'] = 'Classroom title specified by the creator';
$string['privacy:metadata:wims_classes:userdata'] = 'User data';
$string['privacy:metadata:wims_classes:useremail'] = 'Email specified by the class creator, used to create the classroom on the WIMS server.';
$string['privacy:metadata:wims_classes:userfirstname'] = 'First name specified by the class creator, used to create the classroom on the WIMS server';
$string['privacy:metadata:wims_classes:userinstitution'] = 'Institution name specified by the class creator is sent from Moodle to the external WIMS classroom.';
$string['privacy:metadata:wims_classes:userlastname'] = 'Last name specified by the class creator, used to create the classroom on the WIMS server';
$string['privacy:metadata:wims_server'] = 'In order to integrate with a remote WIMS classroom, user data needs to be exchanged with that classroom.';
$string['privacy:metadata:wims_server:fullname'] = 'The user full name is sent from Moodle to the external WIMS classroom.';
$string['privacy:metadata:wims_server:userid'] = 'The user id is sent from Moodle to the external WIMS classroom.';
$string['restore_or_new'] = 'You can either restore a previous backup or create an empty class.';
$string['serversettings'] = 'WIMS Server Settings';

$string['sheetexpiry'] = 'Expiry Date';
$string['sheetgraded'] = 'Track Grades';
$string['sheettitle'] = 'Title';
$string['sheettypeexams'] = 'Exam:';
$string['sheettypeworksheets'] = 'Worksheets:';
$string['updatescores'] = 'Update WIMS scores';
$string['useremail'] = 'Course Contact Email';
$string['userfirstname'] = 'Supervisor First Name';
$string['userinstitution'] = 'Institution Name';
$string['userlastname'] = 'Supervisor Last Name';
$string['wims:addinstance'] = 'Add a WIMS class';
$string['wims:view'] = 'Access a WIMS class';
$string['wimsdebugsettings'] = 'WIMS interface debug settings';
$string['wimssettings'] = 'Moodle-Wims Interface Settings';

$string['wimsstatus1'] = 'Active';
$string['wimsstatus2'] = 'Expired';
$string['wimsstatusx'] = 'Inactive';
$string['wrongparamvalue'] = 'The parameter {$a} has a wrong value.';
