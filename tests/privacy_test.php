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
 * Unit tests for mod/wims/classes/privacy.
 *
 * See https://docs.moodle.org/dev/Writing_PHPUnit_tests
 *
 * To run from Moodle root dir : vendor/bin/phpunit mod/wims/tests/privacy_test.php
 *
 * @package   mod_wims
 * @copyright 2020 UCA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_wims\tests;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->dirroot . '/mod/wims/wimsinterface.class.php');

use \core_privacy\tests\provider_testcase;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\approved_contextlist;
use \mod_wims\privacy\provider;
use \mod_wims\wims_interface;

/**
 * Unit tests for mod/wims/classes/privacy/
 *
 * @category  Tests
 * @package   mod_wims
 * @author    Badatos <bado@unice.fr>
 * @copyright 2020 UCA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://github.com/suipnice/moodle-mod_wims
 */
class mod_wims_privacy_testcase extends provider_testcase {

    /**
     * Convenience function to create an instance of a WIMS activity.
     *
     * @param array $params Array of parameters to pass to the generator
     *
     * @return StdClass containing The wims class + the current context.
     */
    protected function create_instance($params = array()) {
        $ret = new \StdClass();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_wims');
        $instance = $generator->create_instance($params);
        $ret->cm = get_coursemodule_from_instance('wims', $instance->id);
        $ret->context = \context_module::instance($ret->cm->id);
        $ret->config = $generator->get_config_for_tests();
        $ret->wims = new wims_interface($ret->config);
        return $ret;
    }


    /**
     * A test for deleting all user data for a given context.
     * Disabled by now. remove the "disabled" prefix to enable it,
     * but make sure you've modified the defaults in settings.php to point to your wims webserver first.
     * (before calling php admin/tool/phpunit/cli/init.php)
     *
     * @return void
     */
    public function disabled_test_delete_data_for_all_users_in_context() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($user1->id, $course->id, 'student');
        $generator->enrol_user($user2->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $instance = $this->create_instance([
            'course' => $course,
            'name' => 'Classe WIMS 01',
        ]);

        $context = $instance->context;
        $cm = $instance->cm;
        $wims = $instance->wims;
        $config = $instance->config;

        // We set an expiration date at today, so WIMS will automatically delete it tomorrow.
        $course->expiration = date('yymd');

        // Start by creating a class on the WIMS server connected to the course.
        $this->assertTrue($wims->selectclassformodule($course, $cm, $config));

        $sitelang = current_language();
        // Connect $user1 to the WIMS class.
        $wims->getstudenturl($user1, $sitelang);
        // Connect $user2 to the WIMS class.
        $wims->getstudenturl($user2, $sitelang);

        // Check if the users exists within the given course.
        $this->assertCount(2, $wims->getuserlist($cm));

        // Delete all user data in this WIMS classroom.
        provider::delete_data_for_all_users_in_context($context);

        // Check if the users still exists within the given course.
        $this->assertCount(0, $wims->getuserlist($cm));
    }

    /**
     * A test for deleting all data for one user.
     *
     * @return void
     */
    public function test_delete_data_for_user() {
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $coursecontext = \context_course::instance($course->id);

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $teacher = $generator->create_user();
        $generator->enrol_user($user1->id, $course->id, 'student');
        $generator->enrol_user($user2->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $instance = $this->create_instance([
            'course' => $course,
            'name' => 'Classe WIMS 01',
        ]);

        $context = $instance->context;
        $cm = $instance->cm;
        $wims = $instance->wims;
        $config = $instance->config;

        $sitelang = current_language();
        // Connect $user1 to the WIMS class.
        $wims->getstudenturl($user1, $sitelang);
        // Connect $user2 to the WIMS class.
        $wims->getstudenturl($user2, $sitelang);
        // Check if the users exists within the given course.
        $this->assertCount(2, $wims->getuserlist($cm));

        // Delete user 2's data.
        $approvedlist = new approved_contextlist($user2, 'mod_wims', [$context->id, $coursecontext->id]);
        provider::delete_data_for_user($approvedlist);

        // Check if user 2 still exists in the given Wims class.
        $wimslogin = $wims->generatewimslogin($user2);
        $this->assertFalse($wims->checkuser($cm, $wimslogin));

    }
}
