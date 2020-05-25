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
 * Respond to gradebook title click
 *
 * @package   mod_wims
 * @category  grade
 * @copyright 2016 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is grade.php - it is called up by Moodle when the user clicks on a gradebook column title.


require(__DIR__.'/../../config.php');
require_once(dirname(__FILE__).'/wimsinterface.class.php');


// GET / POST parameters.

// Course module ID.
$id         = required_param('id', PARAM_INT);
// The grade column that was clicked - identifies the exam, worksheet, etc from which we come.
$itemnumber = required_param('itemnumber', PARAM_INT);
// Graded user ID (optional).
$userid     = optional_param('userid', 0, PARAM_INT);


if (! $cm = get_coursemodule_from_id('wims', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
    print_error('coursemisconf');
}

require_course_login($course, false, $cm);

// Lookup configuration from moodle.
$config = get_config('wims');

// Construct the arguments for the URL.
$urlargs = array( 'id' => $id );

define('WORKSHEET_ID_OFFSET', 1000);
if ($config->usegradepage == 1) {
    // Direct the user to the grade page.
    $urlargs['wimspage']    = WIMS_GRADE_PAGE;
} else if ($itemnumber >= WORKSHEET_ID_OFFSET) {
    // Direct the user to a specific worksheet.
    $urlargs['wimspage']    = WIMS_WORKSHEET;
    $urlargs['wimsidx']     = $itemnumber - WORKSHEET_ID_OFFSET;
} else {
    // Direct the user to a specific exam.
    $urlargs['wimspage']    = WIMS_EXAM;
    $urlargs['wimsidx']     = $itemnumber;
}

// Delegate to view.php page which will look after redirecting to WIMS.
redirect( new moodle_url( '/mod/wims/view.php', $urlargs ) );
