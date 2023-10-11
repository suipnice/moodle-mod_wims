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
 * WIMS module admin settings and defaults
 *
 * @package   mod_wims
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This is settings.php - add code here to handle administration options for the module.

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . "/settingslib.php");

if ($ADMIN->fulltree) {
    // See https://docs.moodle.org/dev/Admin_settings.
    include_once("$CFG->libdir/resourcelib.php");
    include_once("$CFG->libdir/moodlelib.php");

    // WIMS server settings.
    addwimsadminheading($settings, "serversettings");
    addwimsadminsetting($settings, "serverurl", "http://192.168.0.1/wims/");
    addwimsadminsetting($settings, "allowselfsigcerts", 0, ADMIN_SETTING_TYPE_CHECKBOX);
    addwimsadminsetting($settings, "serverpassword", "password");

    // WIMS interaction configuration.
    addwimsadminheading($settings, "wimssettings");
    addwimsadminsetting($settings, "lang", current_language());
    addwimsadminsetting($settings, "defaultinstitution", "Moodle/WIMS");
    addwimsadminsetting($settings, "usenameinlogin", 0, ADMIN_SETTING_TYPE_CHECKBOX);
    addwimsadminsetting($settings, "usegradepage", 0, ADMIN_SETTING_TYPE_CHECKBOX);

    // Debugging options.
    addwimsadminheading($settings, "wimsdebugsettings");
    addwimsadminsetting($settings, "debugviewpage", 0, ADMIN_SETTING_TYPE_CHECKBOX);
    addwimsadminsetting($settings, "debugcron", 0, ADMIN_SETTING_TYPE_CHECKBOX);
    addwimsadminsetting($settings, "debugsettings", 0, ADMIN_SETTING_TYPE_CHECKBOX);
}
