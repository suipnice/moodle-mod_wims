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
 * To enable these tests, you must first add this to your moodle/config.php :
 *   define('PHPUNIT_LONGTEST', true);
 * then, run from Moodle root dir : vendor/bin/phpunit mod/wims/tests/privacy_test.php
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
     * Communication library for interfacing to the WIMS server
     *
     * @var wims_interface
     */
    private $_wims;

    /**
     * Current WIMS activity course module object
     *
     * @var object
     */
    private $_cm;

    /**
     * Current WIMS activity context
     *
     * @var object
     */
    private $_context;

    /**
     * Current Course id
     *
     * @var string
     */
    private $_courseid;

    /**
     * WIMS classroom status
     * Remember if Wims classroom exist
     *
     * @var bool
     */
    private $_wimsstatus;

    /**
     * List of students enroled in the current course
     *
     * @var array
     */
    private $_studentlist;

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
        return $ret;
    }

    /**
     * setUp() is called by Phpunit before each tests
     *
     * @return void
     **/
    protected function setUp() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }
        $this->resetAfterTest(true);

        // We use the same WIMS activity for several tests, for optimization.
        if (!$this->_cm) {

            $generator = provider_testcase::getDataGenerator();
            $course = $generator->create_course();
            $this->_courseid = $course->id;

            $this->_studentlist[0] = $generator->create_user();
            $this->_studentlist[1] = $generator->create_user();
            $teacher = $generator->create_user();
            $generator->enrol_user($this->_studentlist[0]->id, $this->_courseid, 'student');
            $generator->enrol_user($this->_studentlist[1]->id, $this->_courseid, 'student');
            $generator->enrol_user($teacher->id, $this->_courseid, 'editingteacher');

            $instance = $this->create_instance([
                'course' => $course,
                'name' => 'PHPUnit Classroom  01',
            ]);
            $this->_cm = $instance->cm;
            $this->_context = $instance->context;
            $config = $instance->config;
            // Change 0 to 1 to debug.
            $this->_wims = new wims_interface($config, 0, 'plain');
        }
        if (!$this->_wimsstatus) {
            // We set an expiration date at today, so WIMS will automatically delete it tomorrow.
            $params = (object) array('expiration' => date('yymd'));

            // Start by creating a class on the WIMS server connected to the course.
            $this->_wimsstatus = $this->_wims->selectclassformodule($params, $this->_cm, $config);
            if (!$this->_wimsstatus) {
                $this->markTestSkipped("WIMS server at ".$config->serverurl." can't be reached.");
            }
            // Est-ce qu'on fait un cleanclass avant chaque test ?
        }
    }

    /**
     * tearDown() is called by Phpunit after each tests
     *
     * @return void
     **/
    public function tearDown() {
        // Delete all user data in this WIMS classroom.
        provider::delete_data_for_all_users_in_context($this->_context);
    }

    /**
     * A test for deleting all user data for a given context.
     * Disabled by now. remove the "disabled" prefix to enable it,
     * but make sure you've modified the defaults in settings.php to point to your wims webserver first.
     * (before calling php admin/tool/phpunit/cli/init.php)
     *
     * @return void
     */
    public function test_delete_data_for_all_users_in_context() {

        $sitelang = current_language();
        $wims = $this->_wims;
        // Connect user1 to the WIMS class.
        $wims->getstudenturl($this->_studentlist[0], $sitelang);
        // Connect user2 to the WIMS class.
        $wims->getstudenturl($this->_studentlist[1], $sitelang);

        // Check if the users exists within the given course.
        $this->assertCount(2, $wims->getuserlist($this->_cm));

        /* ICI PB : il utilise la $config wims par défaut (https par exemple)
         Je ne vois pas comment changer cela, car c'est au moment du  php admin/tool/phpunit/cli/init.php
         qu'il initialise une instance de Moodle avec les param par défaut.*/

        // Delete all user data in this WIMS classroom.
        provider::delete_data_for_all_users_in_context($this->_context);

        // Check if the users still exists within the given course.
        $this->assertCount(0, $wims->getuserlist($this->_cm));
    }

    /**
     * A test for deleting all data for one user.
     *
     * @return void
     */
    public function test_delete_data_for_user() {

        $coursecontext = \context_course::instance($this->_courseid);

        $wims = $this->_wims;
        $user1 = $this->_studentlist[0];
        $user2 = $this->_studentlist[1];

        // Check that the WIMS class is empty.
        $this->assertCount(0, $wims->getuserlist($this->_cm));

        $sitelang = current_language();
        // Connect user1 to the WIMS class.
        $wims->getstudenturl($user1, $sitelang);
        // Connect $user2 to the WIMS class.
        $wims->getstudenturl($user2, $sitelang);
        // Check that there is 2 users in the WIMS class.
        $this->assertCount(2, $wims->getuserlist($this->_cm));

        // Delete user 2's data.
        $approvedlist = new approved_contextlist($user2, 'mod_wims', [$this->_context->id, $coursecontext->id]);
        provider::delete_data_for_user($approvedlist);

        // Check if user 2 still exists in the given Wims class.
        $wimslogin = $wims->generatewimslogin($user2);
        $this->assertFalse($wims->checkuser($this->_cm, $wimslogin, false));

    }
}
