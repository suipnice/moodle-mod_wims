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
 * @package    mod_wims
 * @copyright  2015 Edunao SAS (contact@edunao.com)
 * @author     Sadge (daniel@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// this is lib.php - add code here for interfacing this module to Moodle internals

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in wims module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function wims_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return false;
        case FEATURE_SHOW_DESCRIPTION:        return false;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function wims_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
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
 * @param object $data
 * @param object $mform
 * @return int new url instance id
 */
function wims_add_instance($data, $mform = null) {
    global $CFG, $DB;
    $data->timecreated = time();
    $data->id = $DB->insert_record('wims', $data);

    return $data->id;
}

/**
 * Updates an instance of the mod_wims in the database.
 * @param object $data An object from the form in mod_form.php.
 * @param mod_wims_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function wims_update_instance($data, $mform) {
    global $CFG, $DB;

    $parameters = array();
    for ($i=0; $i < 100; $i++) {
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

    return true;
}

/**
 * Delete wims instance.
 * @param int $id
 * @return bool true
 */
function wims_delete_instance($id) {
    global $DB;

    if (!$instance = $DB->get_record('wims', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('wims', array('id'=>$url->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function wims_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    if (!$instance = $DB->get_record('wims', array('id'=>$coursemodule->instance),
            'name')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $instance->name;
    $info->icon = null;

    // display as a new window
    $fullurl = "$CFG->wwwroot/mod/wims/view.php?id=$coursemodule->id&amp;redirect=1";
    $info->onclick = "window.open('$fullurl'); return false;";

    return $info;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return a list of page types
 */
function wims_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-wims-*'=>get_string('page-mod-wims-x', 'wims'));
    return $module_pagetype;
}

/**
 * Export URL resource contents
 *
 * @param $cm
 * @param $baseurl
 * @return array of file content
 */
function wims_export_contents($cm, $baseurl) {
    $contents = array();
    return $contents;
}

/**
 * Is a given scale used by the instance of mod_wims?
 *
 * This function returns if a scale is being used by one mod_wims
 * if it has support for grading and scales.
 *
 * @param int $moduleinstanceid ID of an instance of this module.
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by the given mod_wims instance.
 */
function wims_scale_used($moduleinstanceid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('wims', array('id' => $moduleinstanceid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of mod_wims.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any mod_wims instance.
 */
function wims_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('wims', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given mod_wims instance.
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param bool $reset Reset grades in the gradebook.
 * @return void.
 */
function grade_item_update($moduleinstance, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

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
    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('/mod/wims', $moduleinstance->course, 'mod', 'mod_wims', $moduleinstance->id, 0, null, $item);
}

/**
 * Delete grade item for given mod_wims instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return grade_item.
 */
function wims_grade_item_delete($moduleinstance) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('/mod/wims', $moduleinstance->course, 'mod', 'wims',
                        $moduleinstance->id, 0, null, array('deleted' => 1));
}

/**
 * Update mod_wims grades in the gradebook.
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function wims_update_grades($moduleinstance, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();
    grade_update('/mod/wims', $moduleinstance->course, 'mod', 'mod_wims', $moduleinstance->id, 0, $grades);
}
