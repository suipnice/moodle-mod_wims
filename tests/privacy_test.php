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
     *
     * @return void
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $wims = $this->create_instance([
            'course' => $course,
            'name' => 'Classe WIMS 01',
        ]);

        $context = $wims->context;
        $cm = $wims->cm;

        // Connect $user1 to the WIMS class.

        // Connect $user2 to the WIMS class.

        // Check if the users exists within the given course.
        $wimslogin1 = $wims->generatewimslogin($user1);
        $this->assertTrue($wims->wims->checkuser($cm, $wimslogin1));
        $wimslogin2 = $wims->generatewimslogin($user2);
        $this->assertTrue($wims->wims->checkuser($cm, $wimslogin2));
        $this->assertCount(2, $wims->wims->getuserlist($cm));

        // Delete all user data for this assignment.
        provider::delete_data_for_all_users_in_context($context);

        // Check if the user still exists within the given course.
        $this->assertFalse($wims->wims->checkuser($cm, $wimslogin1));
        $this->assertFalse($wims->wims->checkuser($cm, $wimslogin2));
        $this->assertCount(0, $wims->wims->getuserlist($cm));
    }


}
