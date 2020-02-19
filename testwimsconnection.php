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
 * WIMS interface test code
 *
 * @package   mod_wims
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("wimsinterface.class.php");

$config = new StdClass;
$config->serverurl = "http://plateau-saclay.edunao.com:8081/wims/wims.cgi";
$config->serverpassword = "MOODLE";
$config->institution = "the_institution";
$config->supervisorname = "the supervisor";
$config->contactemail = "the.supervisor.email@edunao.com";
$config->lang = "fr";
$config->qcloffset = 100000;
$config->allowselfsigcerts = true;

$wimsdebug = true;
$wif = new wims_interface($config, $wimsdebug);

// Start by establishing that the connection works.
$connectionresult = $wif->testconnection();
echo "<pre>\n";
if ($connectionresult === true) {
    echo "Connection OK\n";
} else {
    echo "Connection FAILED:\n";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg\n";
    }
}
echo "</pre>\n";
