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
 * Plugin upgrade helper functions are defined here.
 *
 * @package   mod_wims
 * @category  upgrade
 * @copyright 2018 Université Nice Sophia Antipolis <pi@unice.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Helper function used by the upgrade.php file.
 *
 * @return void
 */
function mod_wims_helper_function() {
    global $DB;

    // Please note that you should always be performing any task using raw (low
    // level) database access exclusively, avoiding any use of the Moodle APIs.
    //
    // For more information please read the available Moodle documentation:
    // {@link https://docs.moodle.org/dev/Upgrade_API}.
}

/**
 * Add field in DB on upgrade
 *
 * @param object             $dbman     Moodle DB manager
 * @param string|xmldb_table $table     The table to be searched (string name or xmldb_table instance).
 * @param string             $name      of field
 * @param int                $type      XMLDB_TYPE_INTEGER, XMLDB_TYPE_NUMBER, XMLDB_TYPE_CHAR, XMLDB_TYPE_TEXT, XMLDB_TYPE_BINARY
 * @param string             $precision length for integers and chars, two-comma separated numbers for numbers
 * @param bool               $unsigned  XMLDB_UNSIGNED or null (or false)
 * @param bool               $notnull   XMLDB_NOTNULL or null (or false)
 * @param bool               $sequence  XMLDB_SEQUENCE or null (or false)
 * @param mixed              $default   meaningful default o null (or false)
 * @param xmldb_object       $previous  previous xmldb object
 *
 * @return void
 */
function xmldb_wims_addfield($dbman, $table, $name, $type=null,
    $precision=null, $unsigned=null, $notnull=null, $sequence=null,
    $default=null, $previous=null
): void {
    // Instantiate a field object.
    $field = new xmldb_field($name, $type, $precision, $unsigned, $notnull, $sequence, $default, $previous);
    // If the field doesn't already exist in the given table then add it.
    if (!$dbman->field_exists($table, $name)) {
        $dbman->add_field($table, $field);
    }
}

/**
 * Drop a field in DB on upgrade
 *
 * @param object             $dbman     Moodle DB manager (see  https://docs.moodle.org/dev/Data_definition_API)
 * @param string|xmldb_table $tablename The table to be searched (string name or xmldb_table instance).
 * @param string             $fieldname name of field to be deleted
 *
 * @return void
 */
function xmldb_wims_dropfield($dbman, $tablename, $fieldname): void {
    // If the field exist in the given table then delete it.
    $table = new xmldb_table($tablename);
    $field = new xmldb_field($fieldname);
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }
}
