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
 * Moodle interface library for wims
 *
 * @package   mod_wims
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is lib.php - add code here for interfacing this module to Moodle internals.

/**
 * List of features supported in wims module
 *
 * @param string $feature FEATURE_xx constant for requested feature
 *
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @uses FEATURE_BACKUP_MOODLE2
 * @uses FEATURE_SHOW_DESCRIPTION
 *
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function wims_supports($feature): ?bool {
    switch($feature) {
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GROUPMEMBERSONLY:
        case FEATURE_MOD_INTRO:
            return false;

        case FEATURE_GRADE_HAS_GRADE:
            return true;

        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_SHOW_DESCRIPTION:
            return false;

        case FEATURE_COMPLETION_TRACKS_VIEWS:// Marked complete as soon as a user clicks on it.
            return true;
        case FEATURE_COMPLETION_HAS_RULES:// Custom completion rules.
            return false;

        default:
            return null;
    }
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function wims_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param unknown $data the data submitted from the reset course.
 *
 * @return array empty status array
 */
function wims_reset_userdata($data) {
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function wims_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function wims_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add wims instance into the database.
 *
 * @param stdClass          $data  An object from the form in mod_form.php.
 * @param mod_wims_mod_form $mform The form
 *
 * @return int new url instance id
 */
function wims_add_instance($data, $mform = null) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/wims/locallib.php');

    $data->timecreated = time();
    $data->id = $DB->insert_record('wims', $data);
    wims_update_calendar($data, $data->coursemodule);

    return $data->id;
}

/**
 * Updates an instance of the mod_wims in the database.
 *
 * @param stdClass          $data  An object from the form in mod_form.php.
 * @param mod_wims_mod_form $mform The form.
 *
 * @return bool True if successful, false otherwise.
 */
function wims_update_instance($data, $mform) {
    global $CFG, $DB;

    require_once($CFG->dirroot.'/mod/wims/locallib.php');

    $parameters = array();
    for ($i = 0; $i < 100; $i++) {
        $parameter = "parameter_$i";
        $variable  = "variable_$i";
        if (empty($data->$parameter) or empty($data->$variable)) {
            continue;
        }
        $parameters[$data->$parameter] = $data->$variable;
    }
    $data->parameters = serialize($parameters);

    $data->timemodified = time();
    $data->id           = $data->instance;

    $DB->update_record('wims', $data);
    wims_update_calendar($data, $data->coursemodule);

    return true;
}

/**
 * Delete WIMS instance.
 *
 * @param int $id instance id
 *
 * @return bool true
 */
function wims_delete_instance($id) {
    global $DB;

    if (!$instance = $DB->get_record('wims', array('id' => $id))) {
        return false;
    }

    // Note: all context files are deleted automatically.
    $DB->delete_records('wims', array('id' => $id));

    wims_grade_item_delete($instance);

    $events = $DB->get_records('event', array('modulename' => 'wims', 'instance' => $id));
    foreach ($events as $event) {
        $event = calendar_event::load($event);
        $event->delete();
    }

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @param object $coursemodule Course module
 *
 * @return cached_cm_info info
 */
function wims_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    if (!$instance = $DB->get_record('wims', array('id' => $coursemodule->instance),
            'name')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $instance->name;
    $info->icon = null;

    // Display as a new window.
    $fullurl = "$CFG->wwwroot/mod/wims/view.php?id=$coursemodule->id&amp;redirect=1";
    $info->onclick = "window.open('$fullurl'); return false;";

    return $info;
}

/**
 * Return a list of page types
 *
 * @param string   $pagetype       current page type
 * @param stdClass $parentcontext  Block's parent context
 * @param stdClass $currentcontext Current context of block
 *
 * @return a list of page types
 */
function wims_page_type_list($pagetype, $parentcontext, $currentcontext) {
    return array('mod-wims-*' => get_string('page-mod-wims-x', 'wims'));
}

/**
 * Export URL resource contents
 *
 * @param object  $cm      course module
 * @param unknown $baseurl base url
 *
 * @return array of file content
 */
function wims_export_contents($cm, $baseurl) {
    $contents = array();
    return $contents;
}

/**
 * Is a given scale used by the instance of mod_wims?
 *
 * As all WIMS grades use the "value" type, "scales" are never used.
 *
 * @param int $moduleinstanceid ID of an instance of this module.
 * @param int $scaleid          ID of the scale.
 *
 * @return bool True if the scale is used by the given mod_wims instance.
 */
function wims_scale_used($moduleinstanceid, $scaleid) {
    return false;
    /*global $DB;

    if ($scaleid && $DB->record_exists('wims', array('id' => $moduleinstanceid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }*/
}

/**
 * Checks if scale is being used by any instance of mod_wims.
 *
 * As all WIMS grades use the "value" type, "scales" are never used.
 *
 * @param int $scaleid ID of the scale.
 *
 * @return bool True if the scale is used by any mod_wims instance.
 */
function wims_scale_used_anywhere($scaleid) {
    return false;
    /*global $DB;

    if ($scaleid and $DB->record_exists('wims', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }*/
}

/**
 * Creates or updates grade item for the given mod_wims instance.
 *
 * Needed by {core_grades\grade_update_mod_grades()} in lib/gradelib.php.
 *
 * @param object       $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param array|object $grades         optional array/object of grade(s); 'reset' means reset grades in gradebook
 *
 * @category grade
 *
 * @return int 0 if ok, error code otherwise
 */
function wims_grade_item_update($moduleinstance, $grades = null) {
    global $CFG;
    include_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($moduleinstance->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($moduleinstance->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $moduleinstance->grade;
        $item['grademin']  = 0;
    } else if ($moduleinstance->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$moduleinstance->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($grades === 'reset') {
        $item['reset'] = true;
        $grades = null;
    }

    return grade_update('/mod/wims', $moduleinstance->course, 'mod', 'wims', $moduleinstance->id, 0, null, $item);
}

/**
 * Delete grade item for given mod_wims instance.
 *
 * @param stdClass $moduleinstance Instance object.
 *
 * @return int 0 if ok, error code otherwise
 */
function wims_grade_item_delete($moduleinstance) {
    global $CFG;
    include_once($CFG->libdir.'/gradelib.php');

    return grade_update('/mod/wims', $moduleinstance->course, 'mod', 'wims',
                        $moduleinstance->id, 0, null, array('deleted' => 1));
}

/**
 * Update mod_wims grades in the gradebook.
 *
 * Needed by {core_grades\grade_update_mod_grades()} in lib/gradelib.php.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int      $userid         Update grade of specific user only, 0 means all participants.
 *
 * @return int 0 if ok, error code otherwise
 */
function wims_update_grades($moduleinstance, $userid = 0) {
    /*
    global $CFG, $DB;
    include_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();
    return grade_update('/mod/wims', $moduleinstance->course, 'mod', 'wims', $moduleinstance->id, 0, $grades);
    */

    // WIMS doesn't have its own grade table so the only thing to do is update the grade item.
    return wims_grade_item_update($moduleinstance);
}

