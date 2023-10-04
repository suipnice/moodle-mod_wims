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
 * @copyright 2015 Edunao SAS <contact@edunao.com> / 2022 UCA
 * @author    Sadge <daniel@edunao.com> / Badatos <bado@unice.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is view.php - add all view routines here (for generating output for author, instructor & student).


require(__DIR__ . '/../../config.php');
require_once(dirname(__FILE__) . '/wimsinterface.class.php');
require_once($CFG->libdir . '/completionlib.php');

use mod_wims\wims_interface;

// GET / _POST parameters.

$id = optional_param('id', 0, PARAM_INT);                     // Course module ID.
$urltype = optional_param('wimspage', WIMS_HOME_PAGE, PARAM_INT);  // Type of page to view in WIMS.
$urlarg = optional_param('wimsidx', null, PARAM_INT);             // Index of the page to view.
$mode = optional_param('mode', null, PARAM_ALPHANUMEXT);        // Optional mode  (create new class, restore backup...).
$backupyear = optional_param('backup_year', null, PARAM_INT);         // Optional year of the class backup to be restored
// (when mode=restore_backup).


// Data from Moodle.
if ($id) {
    $cm = get_coursemodule_from_id('wims', $id, 0, false, MUST_EXIST);
    $instance = $DB->get_record('wims', ['id' => $cm->instance], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $config = get_config('wims');
} else {
    throw new moodle_exception('missingparam', 'error', '', 'id');
}

// Sanity tests.
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/wims:view', $context);


// Moodle event logging & state update.
$params = [
    'context' => $context,
    'objectid' => $instance->id,
];
$event = mod_wims\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('wims', $instance);
$event->trigger();


/**
 * Raise an error in HTML format.
 *
 * @param string $mainmsg   Error Title
 * @param array  $errormsgs List of errors
 *
 * @return void
 */
function raisewimserror($mainmsg, $errormsgs): void {
    echo "<h2>" . $mainmsg . "</h2><div class=\"alert alert-danger\">";
    foreach ($errormsgs as $msg) {
        echo "&rarr; $msg<br/>";
    }
    echo "</div>";
}

/**
 * Output Moodle course header for WIMS.
 *
 * @param mixed  $course       Current course
 * @param string $instancename Current instance title
 * @param mixed  $cm           Course module object
 *
 * @return void
 */
function outputheader($course, $instancename, $cm): void {
    global $PAGE, $OUTPUT;
    $PAGE->set_pagelayout('incourse');
    $pagetitle = strip_tags($course->shortname . ': ' . format_string($instancename));
    $PAGE->set_title($pagetitle);
    $PAGE->set_url('/mod/wims/view.php', ['id' => $cm->id]);
    $PAGE->set_heading($instancename);
    $PAGE->set_cm($cm, $course);

    // Print the page header.
    echo $OUTPUT->header();
}


// Instantiate a wims interface.
$wims = new wims_interface($config, $config->debugviewpage);

// Check current user role.
$isteacher = has_capability('moodle/course:manageactivities', $context);

// Sanitize "mode" parameter.
if ($mode != '') {
    if ($isteacher) {
        if (!in_array($mode, ['create_new', 'restore_backup'])) {
            throw new moodle_exception('wrongparamvalue', 'mod_wims', '', 'mode');
        }
    } else {
        // Do not allow student to alter class.
        $mode = '';
    }
}

if ($mode === "restore_backup") {
    // Ensure backupyear given param is a real year.
    $backupyear = max(2000, min(3000, $backupyear));
    // Restore the required class, and try to connect to it.
    $wimsresult = $wims->restoreclassbackup($course, $cm, $backupyear);
} else {
    // Start by connecting to the course on the WIMS server (and instantiate the course if required).
    $wimsresult = $wims->selectclassformodule($course, $cm, $mode);
}
if (!$wimsresult["status"]) {
    outputheader($course, $instance->name, $cm);
    if (isset($wims->errormsgs)) {
        $lasterror = end($wims->errormsgs);
    } else {
        $lasterror = "";
    }
    if (strpos($lasterror, "not existing") !== false) {
        if ($isteacher) {
            echo('<div class="alert alert-danger">' . get_string('class_deleted_with_id', 'wims', $wimsresult['qcl']) . '</div>');

            // List Backups on WIMS server for this class.
            if ($wimsresult['total'] > 0) {
                echo('<p>' . get_string('restore_or_new', 'wims') . '</p>');
                echo('<div class="row">');
                echo('<div class="col-md">');
                $url = new moodle_url('/mod/wims/view.php');
                echo('<form action="' . $url . '" method="get">');
                echo('<input type="hidden" name="id" value="' . $id . '"/>');
                echo('<input type="hidden" name="mode" value="restore_backup"/>');

                echo('<fieldset class="border p-3"><legend>' . get_string('backup_legend', 'wims') . '</legend>');

                if ($wimsresult['total'] > 1) {
                    echo('<p>' . get_string('backups_found', 'wims', $wimsresult['total']) . '</p>');
                } else {
                    echo('<p>' . get_string('backup_found', 'wims') . '</p>');
                }
                echo('<div class="row form-group"><label class="col-sm-3 col-form-label" for="class_backup">');
                echo(get_string('backup_select', 'wims') . '</label>');
                echo('<div class="col-sm-9">');
                echo('<select class="form-control" id="class_backup" name="backup_year" aria-describedby="backupHelp">');
                foreach ($wimsresult['restorable'] as $year => $v) {
                    // We don't need $v, as we requested only backups with id=$qcl.
                    echo("<option value=\"{$year}\">{$year}</p>");
                }
                echo('</select>');
                echo('<small id="backupHelp" class="form-text text-muted">' . get_string('backup_help', 'wims') . '</small>');
                echo('</div></div>');
                echo('<div class="form-group"><button class="btn btn-primary" type="submit">');
                echo(get_string('backup_restore', 'wims') . '</button></div></fieldset>');
                echo('</form></div>');
            }
            // Or create a new empty WIMS class.
            echo('<div class="col-md">');
            echo(' <fieldset class="form-group border p-3"><legend>' . get_string('create_new_legend', 'wims') . '</legend>');
            echo('  <p>' . get_string('create_class_desc', 'wims') . '</p>');
            $url = new moodle_url('/mod/wims/view.php', ['mode' => 'create_new', 'id' => $id]);
            echo('  <div class="form-group"><a class="btn btn-primary" href="' . $url . '" role="button">');
            echo(get_string('create_new_class', 'wims') . '</a></div></fieldset></div>');
            echo('</div>');
            if ($wimsresult['total'] > 0) {
                echo('</div>');
            }
        } else {
            echo('<div class="alert alert-danger">' . get_string('class_deleted', 'mod_wims', $wimsresult['qcl']) . '</div>');
        }
    } else if (strpos($lasterror, "connection refused by requested class") !== false) {
        $wims->errormsgs[] = get_string('class_select_refused_desc', 'wims');
        raisewimserror(get_string('class_select_refused_title', 'wims'), $wims->errormsgs);
    } else {
        $wims->errormsgs[] = get_string('class_select_failed_desc', 'wims');
        raisewimserror(get_string('class_select_failed_title', 'wims'), $wims->errormsgs);
    }
    $debugmsgs = $wims->getdebugmsgs();
    if (!empty($debugmsgs)) {
        echo('<h2>Debug log:</h2><pre class="pre-scrollable debug_wims">');
        foreach ($debugmsgs as $msg) {
            echo("$msg\n");
        }
        echo("</pre>");
    }

    // Finish the page.
    echo $OUTPUT->footer();
} else {
    // WIMS Class exist, and server is up.

    // Mark the activity completed.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    // If we're a teacher then we need the supervisor url otherwise we need the student url.
    $sitelang = current_language();
    if ($isteacher) {
        $url = $wims->getteacherurl($sitelang, $urltype, $urlarg);
    } else {
        $url = $wims->getstudenturl($USER, $sitelang, $urltype, $urlarg);
    }

    // If we've failed to get hold of a plausible url then bomb out with an error message.
    if ($url == null) {
        outputheader($course, $instance->name, $cm);
        raisewimserror("WIMS User Authentication FAILED", $wims->errormsgs);
        echo $OUTPUT->footer();
    } else {
        // Render the output - by executing a redirect to WIMS.
        redirect($url);
    }
}
