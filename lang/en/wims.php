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
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General strings - for use selecting a module type, or listing module types, etc...
$string['modulename']                  = 'WIMS Course';
$string['modulenameplural']            = 'WIMS courses';
$string['modulename_help']             = 'Integrate a WIMS virtual classroom into your course';

// Plugin administration strings.
$string['pluginadministration']        = 'WIMS module administration';
$string['pluginname']                  = 'WIMS';

// Admin settings - server configuration.
$string['serversettings']              = 'WIMS Server Settings';
$string['adminnameserverurl']          = 'WIMS Server URL';
$string['admindescserverurl']          = '';

$string['adminnameallowselfsigcerts']  = 'Allow self-signed certificates';
$string['admindescallowselfsigcerts']  = '';

$string['adminnameserverpassword']     = 'WIMS Server connection password';
$string['admindescserverpassword']     = 'The one you\'ve defined in the files in ".connexions" directory on WIMS server.';

$string['adminnameqcloffset']          = 'WIMS Course Numbering Offset';
$string['admindescqcloffset']          = 'Between 11111 and 10^9';

// Admin settings - Interface settings.
$string['wimssettings']                = 'Moodle-Wims Interface Settings';
$string['adminnamelang']               = 'Course Language (default value)';
$string['admindesclang']               = 'Must be one of ca, cn, en, es, fr, it, nl, si, tw, de';

$string['adminnamedefaultinstitution'] = 'Institution Name (default value)';
$string['admindescdefaultinstitution'] = '';

$string['adminnameusenameinlogin']     = 'Include user name in WIMS login';
$string['admindescusenameinlogin']     = '';

$string['adminnameusegradepage']       = 'Redirect Moodle Gradebook links to WIMS grade page';
$string['admindescusegradepage']       = '';

// Admin settings - Debug settings.
$string['wimsdebugsettings']           = 'WIMS interface debug settings';
$string['adminnamedebugviewpage']      = 'VIEW debug output';
$string['admindescdebugviewpage']      = '';

$string['adminnamedebugcron']          = 'CRON debug output';
$string['admindescdebugcron']          = '';

$string['adminnamedebugsettings']      = 'SETTINGS debug output';
$string['admindescdebugsettings']      = '';

// Errors Msgs
$string['class_select_failed_title']   = 'Unable to access the WIMS classroom';
$string['class_select_failed_desc']    = 'The server is probably unavailable. Please retest in a few minutes, or notify the site administrator.';

// Instance settings.
$string['name']                        = 'Activity Name';
$string['userinstitution']             = 'Institution Name';
$string['userfirstname']               = 'Supervisor First Name';
$string['userlastname']                = 'Supervisor Last Name';
$string['useremail']                   = 'Course Contact Email';

// Worksheet and exam settings.
$string['sheettypeworksheets']         = 'Worksheets:';
$string['sheettypeexams']              = 'Exam:';
$string['sheettitle']                  = 'Title';
$string['sheetgraded']                 = 'Track Grades';
$string['sheetexpiry']                 = 'Expiry Date';
$string['wimsstatus1']                 = 'Active';
$string['wimsstatus2']                 = 'Expired';
$string['wimsstatusx']                 = 'Inactive';

// Misc strings.
$string['page-mod-wims-x']             = 'Any WIMS module page';
$string['modulename_link']             = 'mod/wims/view';

// Scheduled tasks.
$string['updatescores']             = 'Update WIMS scores';
