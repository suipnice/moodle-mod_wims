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
 * Display WIMS course elements.
 *
 * @package   mod_wims
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is view.php - add all view routines here (for generating output for author, instructor & student).


require(__DIR__.'/../../config.php');
require_once(dirname(__FILE__).'/wimsinterface.class.php');
require_once($CFG->libdir . '/completionlib.php');


// _GET / _POST parameters.

$id         = optional_param('id', 0, PARAM_INT);                     // Course module ID
$urltype    = optional_param('wimspage', WIMS_HOME_PAGE, PARAM_INT);  // type of page to view in wims
$urlarg     = optional_param('wimsidx', null, PARAM_INT);             // Index of the page to view.


// Data from Moodle.
if ($id) {
    $cm = get_coursemodule_from_id('wims', $id, 0, false, MUST_EXIST);
    $instance = $DB->get_record('wims', array('id'=>$cm->instance), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $config = get_config('wims');
} else {
    print_error(get_string('missingidandcmid', mod_wims));
}

// Sanity tests.

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/wims:view', $context);


// Moodle event logging & state update.

$params = array(
    'context' => $context,
    'objectid' => $instance->id
);
$event = \mod_wims\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('wims', $instance);
$event->trigger();


// Work Code.

/**
 * Raise an error.
 *
 * @param string $mainmsg   Error Title
 * @param array  $errormsgs List of errors
 */
function raisewimserror($mainmsg, $errormsgs) {
    echo "<h1>".$mainmsg."</h1>";
    foreach ($errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
    die();
}


// Render the output - by executing a redirect to WIMS.

$PAGE->set_url('/mod/wims/view.php', array('id' => $cm->id));

// Instantiate a wims interface.
$wims=new wims_interface($config, $config->debugviewpage);

// Start by connecting to the course on the WIMS server (and instantiate the course if required).
$wimsresult = $wims->selectclassformodule($course, $cm, $config);
($wimsresult == true)||raisewimserror("WIMS Course Select FAILED", $wims->errormsgs);

// If we're a teacher then we need the supervisor url otherwise we need the student url.
$sitelang = current_language();
$isteacher = has_capability('moodle/course:manageactivities', $context);
if ($isteacher) {
    $url = $wims->getteacherurl($sitelang, $urltype, $urlarg);
} else {
    $url = $wims->getstudenturl($USER, $sitelang, $urltype, $urlarg);
}

// If we've failed to get hold of a plausible url then bomb out with an error message.
($url != null)||raisewimserror("WIMS User Authentication FAILED", $wims->errormsgs);

// Do the redirection.
redirect($url);
