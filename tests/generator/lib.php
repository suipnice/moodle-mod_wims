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

defined('MOODLE_INTERNAL') || die();

/**
 * WIMS module test data generator class
 *
 * @package mod_wims
 * @copyright 2020 UCA
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wims_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG;

        global $_SERVER;
        // Set the server adress that will be communicated to WIMS external service.
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $record = (object)(array)$record;

        $defaultclasssettings = array(
            'name'             => "Test Classroom",
            'userfirstname'    => "Anonymous",
            'userlastname'     => "Supervisor",
            'useremail'        => "noreply@wimsedu.info",
            'userinstitution'  => "Moodle/WIMS"
        );

        foreach ($defaultclasssettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    public function get_config_for_tests() {
        $defaultwimssettings = array(
           'allowselfsigcerts' => '0',
           'debugcron' => '1',
           'debugsettings' => '1',
           'debugviewpage' => '0',
           'defaultinstitution' => 'Moodle/WIMS',
           'lang' => 'fr',
           'qcloffset' => '100000',
           'serverpassword' => 'password',
           'serverurl' => 'http://192.168.56.5/wims/',
           'usegradepage' => '0',
           'usenameinlogin' => '0',
        );
        return (object) $defaultwimssettings;
    }
}
