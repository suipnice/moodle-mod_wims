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
 * WIMS module test data generator.
 *
 * @package   mod_wims
 * @copyright 2020 UCA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * WIMS module test data generator class
 *
 * @package mod_wims
 * @copyright 2020 UCA
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wims_generator extends testing_module_generator {
    /**
     * Create a WIMS activity instance.
     *
     * @param array|stdClass $record data for module being generated. Requires 'course' key (id or full object).
     * @param null|array  $options  general options for course module.
     *
     * @return stdClass  record from module-defined table with additional field cmid (corresponding id in course_modules table)
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;

        global $_SERVER;
        // Set the server adress that will be communicated to WIMS external service.
        // WIMS use it to determine from which IP a student comes.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $record = (object)(array)$record;

        $defaultclasssettings = [
            'name' => "Test Classroom",
            'userfirstname' => "Anonymous",
            'userlastname' => "Supervisor",
            'useremail' => "noreply@wimsedu.info",
            'userinstitution' => "Moodle/WIMS",
        ];

        foreach ($defaultclasssettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * return some options adapted to testing purpose.
     * (but for some tests, Moodle will still use mod_wims default settings)
     *
     * @return stdClass
     */
    public function get_config_for_tests() {
        $defaultwimssettings = [
           'allowselfsigcerts' => '0',
           'debugcron' => '1',
           'debugsettings' => '1',
           'debugviewpage' => '0',
           'defaultinstitution' => 'Moodle/WIMS',
           'lang' => 'fr',
           'serverpassword' => 'password',
           'serverurl' => 'http://192.168.56.5/wims/',
           'usegradepage' => '0',
           'usenameinlogin' => '0',
        ];
        return (object) $defaultwimssettings;
    }
}
