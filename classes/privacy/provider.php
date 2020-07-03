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
 * Privacy implementation for WIMS Plugin.
 * See {@link https://docs.moodle.org/dev/Privacy_API}.
 */

namespace mod_wims\privacy;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/wims/wimsinterface.class.php');

use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\deletion_criteria;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\helper as request_helper;
use \core_privacy\local\metadata\collection;
use \mod_wims\wims_interface;

/**
 * Privacy implementation for WIMS plugin.
 *
 * @category  privacy
 * @package   mod_wims
 * @author    Badatos <bado@unice.fr>
 * @copyright 2020 UCA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://github.com/suipnice/moodle-mod_wims
 */
class provider implements
    // This plugin does store personal user data.
    \core_privacy\local\metadata\provider,

    // This plugin currently implements the original plugin_provider interface.
    \core_privacy\local\request\plugin\provider,

    \core_privacy\local\request\core_userlist_provider
{

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param collection $items The collection to add metadata to.
     *
     * @return collection The array of metadata
     */
    public static function get_metadata(collection $items) : collection {
        // Here we add more items to the collection.

        // Stores grades using the Moodle gradebook api.
        $items->add_subsystem_link(
            'core_grades',
            [],
            'privacy:metadata:core_grades'
        );

        // Data stored in wims db table.
        $collection->add_database_table(
        'wims',
         [
            'name' => 'privacy:metadata:wims:name',
            'userinstitution' => 'privacy:metadata:wims:userinstitution',
            'userfirstname' => 'privacy:metadata:wims:userfirstname',
            'userlastname' => 'privacy:metadata:wims:userlastname',
            'useremail' => 'privacy:metadata:wims:useremail',
             ],
            'privacy:metadata:wims'
        );

        // Data stored in WIMS server.
        $collection->add_external_location_link('wims_server', [
            'userid' => 'privacy:metadata:wims_server:userid',
            'fullname' => 'privacy:metadata:wims_server:fullname',
        ], 'privacy:metadata:wims_server');

        return $items;
    }

    /**
     * Get the list of contexts where the specified user has attempted a WIMS activity
     * To test this function, you can call
     * php admin/tool/task/cli/adhoc_task.php --execute="\tool_dataprivacy\task\process_data_request_task"
     *
     * @param int $userid The user to search.
     *
     * @return contextlist The list of contexts where the user has attempted a WIMS activity.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;
        $cmids = array();

        $wims = new wims_interface(get_config('wims'));

        /* get WIMS user ID */
        $userinfo = $DB->get_record('user', array('id' => $userid ), 'id, firstname, lastname');
        $wimslogin = $wims->generatewimslogin($userinfo);

        /* Get all WIMS activities in Moodle Courses */
        $moduleinfo = $DB->get_record('modules', array('name' => 'wims'));
        $coursemodules = $DB->get_records('course_modules', array('module' => $moduleinfo->id), 'id', 'id,course,instance,section');

        foreach ($coursemodules as $cm) {
            // Make sure the classroom is correctly accessible.
            $isaccessible = $wims->verifyclassaccessible($cm);
            if (!$isaccessible) {
                mtrace('  - ALERT: Ignoring classroom as it is inaccessible - it may not have been setup yet');
                continue;
            }

            // Check if the user exists within the given course module.
            if ($wims->checkuser($cm, $wimslogin)) {
                // User exists, add cm to $cm_ids.
                array_push($cmids, $cm->id);
            }
        }

        $contextlist = new contextlist();

        if (count($cmids) > 0) {
            $params = ['contextlevel' => CONTEXT_MODULE,
                       'moduleid' => $moduleinfo->id ];
            $cmids = implode(',', $cmids);
            $sql = "SELECT ctx.id
                    FROM {course_modules} cm
                    JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                    WHERE cm.id IN ($cmids)
                    AND cm.module = :moduleid";
            mtrace('  - SQL= '.$sql);
            $contextlist->add_from_sql($sql, $params);
        }

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     * See {@link https://docs.moodle.org/dev/Privacy_API#Exporting_data}.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $wims = new wims_interface(get_config('wims'));
        $wimslogin = $wims->generatewimslogin($user);

        // Export data with context.
        foreach ($contextlist->get_contexts() as $context) {
            // Check that the context is a module context.
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            // First, we get all data related to this context stored by Moodle.
            $modwimsdata = request_helper::get_context_data($context, $user);

            writer::with_context($context)->export_data([], $modwimsdata);

            // Then we get data from WIMS Server.
            $cm = get_coursemodule_from_id('wims', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $data = new \stdClass();
            $data->wimslogin = $wimslogin;
            $data->userconfig = $wims->getuserdata($cm, $wimslogin);
            $data->userscores = $wims->getscore($cm, $wimslogin);
            writer::with_context($context)->export_data([get_string('privacy:metadata:wims_classes:userdata', 'mod_wims')], $data);
        }

    }


    /**
     * Delete all data for all users in the specified context (if context is wims).
     * See {@link https://docs.moodle.org/dev/Privacy_API#Delete_for_a_context}.
     *
     * @param \context $context The specific context to delete data for.
     *
     * @return mixed
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('wims', $context->instanceid);
        if (!$cm) {
            return;
        }

        $wims = new wims_interface(get_config('wims'));

        // Delete all user data in WIMS virtual Classroom associated to the $cm.
        $wims->cleanclass($cm);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $count = $contextlist->count();
        if (empty($count)) {
            return;
        }

        $user = $contextlist->get_user();
        $wims = new wims_interface(get_config('wims'));
        $wimslogin = $wims->generatewimslogin($user);
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('wims', $context->instanceid);

            if (!$cm) {
                continue;
            }
            $wims->deluser($cm, $wimslogin);
        }
    }

    /**
     * Get the list of users who have attempted a WIMS activity in the specified a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('wims', $context->instanceid);
        $wims = new wims_interface(get_config('wims'));

        // Make sure the classroom is correctly accessible.
        $isaccessible = $wims->verifyclassaccessible($cm);
        if (!$isaccessible) {
            mtrace('  - ALERT: Ignoring classroom as it is inaccessible - it may not have been setup yet');
            return;
        }

        $wimsuserlist = $wims->getuserlist();
        if (count($wimsuserlist) <= 0) {
            return;
        }
        $userlookup = $wims->builduserlookuptable();

        $useridlist = array();
        foreach ($wimsuserlist as $wimslogin) {
            if (!array_key_exists($wimslogin, $userlookup)) {
                mtrace(' ERROR: Failed to lookup WIMS login in MOODLE users for login: '.$wimslogin);
                continue;
            }
            $useridlist[] = $userlookup[$wimslogin];
        }

        $sql = "SELECT id
                    FROM {user}
                    WHERE id  IN (:useridlist)";

        $userlist->add_from_sql('userid', $sql, ['useridlist' => $context->id]);

    }

    /**
     * Delete multiple users within a single context.
     *
     * @param  approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $userids = $userlist->get_userids();

        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);

        $wims = new wims_interface(get_config('wims'));

        foreach ($userids as $userid) {

            // Get WIMS user ID.
            $userinfo = $DB->get_record('user', array('userid' => $userid ), 'id, firstname, lastname');
            $wimslogin = $wims->generatewimslogin($userinfo);
            if (!$wims->deluser($cm, $wimslogin)) {
                mtrace('  - FAILURE: WIMS - User not deleted');
            }
        }

        // Update this function to delete advanced grading information.
        $gradingmanager = get_grading_manager($context, 'mod_wims');
        $controller = $gradingmanager->get_active_controller();
        if (isset($controller)) {
            $gradeids = $requestdata->get_gradeids();
            // Careful here, if no gradeids are provided then all data is deleted for the context.
            if (!empty($gradeids)) {
                \core_grading\privacy\provider::delete_data_for_instances($context, $gradeids);
            }
        }
    }
}
