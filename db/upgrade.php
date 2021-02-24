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
 * WIMS module version upgrade code
 *
 * @package   mod_wims
 * @category  upgrade
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This file keeps track of upgrades to
 * the resource module
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/upgradelib.php');

/**
 * Execute mod_wims upgrade from the given old version.
 *
 * @param int $oldversion old version
 *
 * @return bool
 */
function xmldb_wims_upgrade($oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();
    $modulename = 'wims';

    // For further information please read the Upgrade API documentation:
    // {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at:
    // {@link https://docs.moodle.org/dev/XMLDB_editor}.

    // Upgrade to version with extra user... fields in database.
    $nextversion = 2015102201;
    if ($oldversion < $nextversion) {
        // Get hold of the module's database table.
        $table = new xmldb_table($modulename);
        // Adding fields to table.
        xmldb_wims_addfield($dbman, $table, 'userinstitution', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL);
        xmldb_wims_addfield($dbman, $table, 'userfirstname', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL);
        xmldb_wims_addfield($dbman, $table, 'userlastname', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL);
        xmldb_wims_addfield($dbman, $table, 'useremail', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL);
        // WIMS savepoint reached.
        upgrade_mod_savepoint(true, $nextversion, $modulename);
    }

    $nextversion = 2020062900;
    if ($oldversion < $nextversion) {
        // Remove "username" from db (redondant with firstname/lastname).
        xmldb_wims_dropfield($dbman, $modulename, 'username');

        // WIMS savepoint reached.
        upgrade_mod_savepoint(true, $nextversion, $modulename);
    }

    return true;
}
