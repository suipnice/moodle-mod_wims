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

$course = new StdClass;
$course->lang = "en";

$coursemodule = new StdClass;
$coursemodule->id = 1494306;
$coursemodule->name = "module_001";

$user = new StdClass;
/*$user->firstname = "my_first_name";
$user->lastname = "my_last_name";
$user->username = "my_login";*/
$user->firstname = "albert";
$user->lastname = "tester";
$user->username = "atester";

$config = new StdClass;
$config->serverurl = "http://plateau-saclay.edunao.com:8081/wims/wims.cgi";
$config->serverpassword = "MOODLE";
$config->institution = "the_institution";
$config->supervisorname = "the supervisor";
$config->contactemail = "the.supervisor.email@edunao.com";
$config->lang = "fr";
$config->qcloffset = 100000;

$courseconfigupdate = array();
$courseconfigupdate["firstname"] = "Sadge";
$courseconfigupdate["exams"] = array();
$courseconfigupdate["worksheets"] = array();
/*
$courseconfigupdate["exams"][] = array("title" => "updated exam title", "duration" => "60", "expiration" => '20171111');
$courseconfigupdate["worksheets"][] = array();
$courseconfigupdate["worksheets"][] = array("title" => "updated ws title", "expiration" => '20161212');
*/
$courseconfigupdate["exams"][1] = array("title" => "updated exam title", "duration" => "60", "expiration" => '20171111');
$courseconfigupdate["worksheets"][2] = array("title" => "updated ws title (*)", "expiration" => '20161212');

$wimsdebug = true;
$wif = new wims_interface($config, $wimsdebug);

// Start by establishing that the connection works.
if ($wif->testconnection() == true) {
    echo "Connection OK<br/>";
} else {
    echo "Connection FAILED:<br/>";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
}

// Then connect to the course.
if ($wif->selectclassformodule($course, $coursemodule, $config) == true) {
    echo "Course Selected OK<br/>";
} else {
    echo "Course Select FAILED:<br/>";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
}

// Get a supervisor URL.
$teacherurl = $wif->getteacherurl("en");
if ($teacherurl != null) {
    echo "Teacher: ";
    echo "<a href='$teacherurl'>$teacherurl</a><br/>";
} else {
    echo "FAILER to fetch Teacher URL...<br/>";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
}
$teacherurl = $wif->getteacherurl("en", WIMS_WORKSHEET_URL, 2);
if ($teacherurl != null) {
    echo "Teacher: ";
    echo "<a href='$teacherurl'>$teacherurl</a><br/>";
} else {
    echo "FAILER to fetch Teacher URL...<br/>";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
}
$teacherurl = $wif->getteacherurl("en", WIMS_EXAM_URL, 1);
if ($teacherurl != null) {
    echo "Teacher: ";
    echo "<a href='$teacherurl'>$teacherurl</a><br/>";
} else {
    echo "FAILER to fetch Teacher URL...<br/>";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
}

// Get a student URL.
$studenturl = $wif->getstudenturl($user, "fr");
if ($studenturl != null) {
    echo "Student: ";
    echo "<a href='$studenturl'>$studenturl</a><br/>";
} else {
    echo "FAILER to fetch Student URL...<br/>";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
}
$studenturl = $wif->getstudenturl($user, "fr", WIMS_WORKSHEET_URL, 2);
if ($studenturl != null) {
    echo "Student: ";
    echo "<a href='$studenturl'>$studenturl</a><br/>";
} else {
    echo "FAILER to fetch Student URL...<br/>";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
}
$studenturl = $wif->getstudenturl($user, "fr", WIMS_EXAM_URL, 1);
if ($studenturl != null) {
    echo "Student: ";
    echo "<a href='$studenturl'>$studenturl</a><br/>";
} else {
    echo "FAILER to fetch Student URL...<br/>";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
}

// Update config information.
$configupdateresult = $wif->updateclassconfigformodule($coursemodule, $courseconfigupdate);
if ($configupdateresult != null) {
    echo "Course config updated OK<br/>";
} else {
    echo "FAILED to update course config...<br/>";
    foreach ($wif->errormsgs as $msg) {
        echo "&gt; $msg<br/>";
    }
}

// Fetch the config information.
$configfromwims = $wif->getclassconfigformodule($coursemodule);
if ($configfromwims) {
    echo "<h1>Class Config</h1>";
    echo "<table>";
    foreach ($configfromwims as $key => $val) {
        if (($key != "worksheets")&&($key != "exams")) {
            echo "<tr><td>$key</td><td>$val</td></tr>";
        }
    }
    echo "</table>";
    foreach (array("worksheets", "exams") as $typeidx => $sheettype) {
        foreach ($configfromwims[$sheettype] as $sheetidx => $sheetprops) {
            echo "<h2>$sheettype $sheetidx</h2>";
            echo "<table>";
            foreach ($sheetprops as $key => $val) {
                echo "<tr><td>$key</td><td>$val</td></tr>";
            }
            echo "</table>";
        }
    }
} else {
    echo "GetClassConfig() - failed";
}
