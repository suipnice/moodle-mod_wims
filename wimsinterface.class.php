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
 * Moodle/WIMS communication library
 *
 * @package   mod_wims
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_wims;
defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__)."/wimscommswrapper.class.php");

// Defines used for wims_interface::getstudenturl() and wims_interface::getteacherurl() calls.
define('WIMS_HOME_PAGE', 1);
define('WIMS_GRADE_PAGE', 2);
define('WIMS_WORKSHEET', 3);
define('WIMS_EXAM', 4);

/**
 * Low level communication library for interfacing to a WIMS server
 *
 * @category  external
 * @package   mod_wims
 * @author    Sadge <daniel@edunao.com>
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://github.com/suipnice/moodle-mod_wims
 */
class wims_interface{

    /**
     * In the case where an error is encounterd this variable will contain error message as an array of lines.
     * TODO: is it still used ?
     *
     * @var array  $erromsgs
     */
    public $erromsgs;

    /**
     * Related wims_comms_wrapper object
     *
     * @var wims_comms_wrapper $_wims
     */
    private $_wims;

    /**
     * Querried WIMS class ID
     *
     * @var string $_qcl
     */
    private $_qcl;

    /**
     * Local course ID (remote class for WIMS)
     *
     * @var string $_rcl
     */
    private $_rcl;

    /**
     * The WIMS configuration object
     *
     * @var object $_config
     */
    private $_config;

    /**
     * Ctor (the class constructor)
     * stores away the supplied parametersbut performs no actions
     *
     * @param object  $config       the WIMS configuration object
     * @param integer $debug        enables verbose output when set true
     * @param string  $debugformat indicates in which format (html / plain text) debug must be formatted in
     *
     * @return void
     */
    public function __construct($config, $debug=0, $debugformat='html') {
        $allowselfsignedcertificates =
            (property_exists($config, 'allowselfsigcerts')
            && ($config->allowselfsigcerts == true)) ? true : false;
        $this->_wims = new wims_comms_wrapper(
            $config->serverurl,
            $config->serverpassword,
            $allowselfsignedcertificates,
            $debugformat
        );
        $this->_wims->debug = $debug;
        $this->_config = $config;
    }

    /**
     * Attempt to connect to the WIMS server and verify that it responds with an OK message
     *
     * @return null|true if the connection attempt succeeded, null if it failed
     */
    public function testconnection() {
        // Try connecting to the server using adm/raw WIMS API.

        // If both of the connection tests succeeded then we're done.
        if ($this->_wims->checkident()) {
            return true;
        }
        // The connection test failed, so construst an error message and return NULL.
        $this->errormsgs = ['WIMS connection test failed'];
        return null;
    }

    /**
     * Build a lookup table to get Moodle user ids from wimslogin.
     *
     * @return array the lookup table
     */
    public function builduserlookuptable() {
        global $DB;
        // Fetch the complete user list (except deleted and supended) from Moodle (and hope that we don't run out of RAM).
        $userrecords = $DB->get_records('user', array('deleted' => 0, 'suspended' => 0 ), '', 'id, firstname, lastname');

        $userlookup = array();
        foreach ($userrecords as $userinfo) {
            $wimslogin = $wims->generatewimslogin($userinfo);
            $userlookup[$wimslogin] = $userinfo->id;
        }
        return $userlookup;
    }

    /**
     * Select the class on the wims server with which to work (for a given Moodle WIMS module instance)
     * If the class doesn't exist then this routine will create it.
     *
     * @param object $course the current Moodle course object
     * @param object $cm     the course module that the wims class is bound to. It should include:
     *                       integer $cm->id   the course module's unique id
     *                       string  $cm->name the course module instance name
     *
     * @return true on success, null if one failed
     */
    public function selectclassformodule($course, $cm) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);

        // Work out what language to use
        // by default we use the config language
        // but if the course includes an override then we need to use it.
        $this->lang = (property_exists($course, "lang")&&($course->lang != "")) ? $course->lang : $this->_config->lang;

        // Try to connect and drop out if we managed it.
        $checkresult = $this->_wims->checkclass($this->_qcl, $this->_rcl);
        if ($checkresult === true) {
            return true;
        }

        // Try to create the WIMS class.
        global $DB;
        $wimsinfo = $DB->get_record('wims', array('id' => $cm->instance));
        $randomvalue1 = rand(100000, 999999);
        $data1 =
            "description=$cm->name"."\n".
            "institution=$wimsinfo->userinstitution"."\n".
            "supervisor=".$wimsinfo->userfirstname." ".$wimsinfo->userlastname."\n".
            "email=$wimsinfo->useremail"."\n".
            "password=Pwd$randomvalue1"."\n".
            "lang=$this->lang"."\n".
            "secure=all"."\n";
        $randomvalue2 = rand(100000, 999999);
        $data2 =
            "lastname=$wimsinfo->userlastname"."\n".
            "firstname=$wimsinfo->userfirstname"."\n".
            "password=Pwd$randomvalue2"."\n";
        $addresult = $this->_wims->addclass($this->_qcl, $this->_rcl, $data1, $data2);

        // Ensure that everything went to plan.
        if ($addresult !== true) {
            $this->errormsgs = $this->_wims->jsondata->message;
            return null;
        }

        // Try to modify the class that we just created to set the connection rights.
        $data1 = $this->_constructconnectsline();
        $modresult = $this->_wims->updateclass($this->_qcl, $this->_rcl, $data1);

        // Ensure that everything went to plan.
        if ($modresult === true) {
            return true;
        } else {
            $this->errormsgs = $this->_wims->jsondata->message;
            return null;
        }
    }

    /**
     * Attempt to access a WIMS class for a given Moodle module - to verify whether it is generally accessible
     *
     * @param object $cm the course module that the wims class is bound to. It should include:
     *                   integer $cm->id the course module's unique id
     *
     * @return true on success, null if on failure
     */
    public function verifyclassaccessible($cm) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);

        // Delegate to the wims comms wrapper to do the work.
        return $this->_wims->checkclass($this->_qcl, $this->_rcl, true) ? true : null;
    }

    /**
     * Create a WIMS login from a user record
     *
     * @param object $user including the following:
     *                     string $user->id        the user's unique id from within Moodle
     *                     string $user->firstname the user's first name
     *                     string $user->lastname  the user's last name
     *
     * @return string login for use in wims
     */
    public function generatewimslogin($user) {
        // Lookup our configuration to see whether or not we are supposed to use the user name in the WIMS login.
        // Using the user name in the WIMS login has the advantage of making
        // the login more readable but the disadvantage of breaking the link between Moodle and
        // WIMS accounts if ever the user's profile is updated in Moodle.
        if ($this->_config->usenameinlogin == 1) {
            // Start by assembling the basic string parts that we're interested in.
            $initial  = ($user->firstname) ? $user->firstname[0] : '';
            $fullname = strtolower($initial . $user->lastname);
            // Now filter out all of the characters that we don't like in the user name.
            $cleanname = '';
            // We limit the name length to 16 characters because of an internal limit in WIMS.
            for ($i = 0; $i < strlen($fullname)&&strlen($cleanname) < 16; ++$i) {
                $letter = $fullname[$i];
                if ($letter >= 'a' && $letter <= 'z') {
                    $cleanname .= $letter;
                }
            }
            // Add the user id on the end and call it done.
            $result = $cleanname . $user->id;
            return $result;
        } else {
            // Add the user id on the end and call it done.
            $result = 'moodleuser' . $user->id;
            return $result;
        }
    }

    /**
     * Create a WIMS session for the given user, connecting them to this course and return an access url
     *
     * @param object $user        including the following:
     *                            string $user->firstname the user's first name
     *                            string $user->lastname the user's last name
     * @param string $currentlang current language (to force the wims site language to match the Moodle language)
     * @param string $urltype     the type of url required (defaults to 'home page')
     * @param string $arg         the argument to be used for selecting which worksheet or exam page to display,
     *                            depending on $urltype
     *
     * @return string connection URL for the user to use to access the session if the operation succeeded, null if it failed
     */
    public function getstudenturl($user, $currentlang, $urltype=WIMS_HOME_PAGE, $arg=null) {
        // Derive the WIMS login from the MOODLE user data record.
        $login = $this->generatewimslogin($user);

        // Check if the user exists within the given course.
        $checkresult = $this->_wims->checkuser($this->_qcl, $this->_rcl, $login);
        if ($checkresult == null) {
            // The user doesn't exist so try to create them.
            $firstname = $user->firstname;
            $lastname = $user->lastname;
            $addresult = $this->_wims->adduser($this->_qcl, $this->_rcl, $firstname, $lastname, $login);
            if ($addresult == null) {
                // If the call to adduser failed then deal with it.
                $this->errormsgs = $this->_wims->jsondata->message;
                return null;
            }
        }

        // The user should exist now so create the session and return it's access url.
        switch($urltype) {
            case WIMS_HOME_PAGE  :
                return $this->_gethomepageurlforlogin($login, $currentlang);
            case WIMS_GRADE_PAGE :
                return $this->_getscorepageurlforlogin($login, $currentlang);
            case WIMS_WORKSHEET  :
                return $this->_getworksheeturlforlogin($login, $currentlang, $arg);
            case WIMS_EXAM       :
                return $this->_getexamurlforlogin($login, $currentlang, $arg);
            default :
                throw new Exception('BUG: Bad urltype parameter '.$urltype);
        }
    }

    /**
     * Create a WIMS supervisor session for this course and return an access url
     *
     * @param string $currentlang current language
     *                            (to force the WIMS site language to match the Moodle language)
     * @param string $urltype     the type of url required (defaults to 'home page')
     * @param string $arg         the argument to be used for selecting which worksheet or exam page to display,
     *                            depending on $urltype
     *
     * @return string connection URL for the user to use to access the session if the operation succeeded, null if it failed
     */
    public function getteacherurl($currentlang, $urltype=WIMS_HOME_PAGE, $arg=null) {
        // The "supervisor" login is a special login bound by WIMS,
        // using it we get the url to the teacher's page and not the student page.
        $login = "supervisor";
        switch($urltype) {
            case WIMS_HOME_PAGE  :
                return $this->_gethomepageurlforlogin($login, $currentlang);
            case WIMS_GRADE_PAGE :
                return $this->_getscorepageurlforlogin($login, $currentlang);
            case WIMS_WORKSHEET  :
                return $this->_getworksheeturlforlogin($login, $currentlang, $arg);
            case WIMS_EXAM       :
                return $this->_getexamurlforlogin($login, $currentlang, $arg);
            default:
                throw new Exception('BUG: Bad urltype parameter '.$urltype);
        }
    }

    /**
     * Fetch the class config from the WIMS server (for a given Moodle WIMS module instance)
     * Note that it is valid for this method to be called for classes that have
     * not yet been instantiated on the WIMS server
     *
     * @param object $cm the course module that the wims class is bound to
     *
     * @return array|null associative array course property values on success or null on fail
     */
    public function getclassconfigformodule($cm) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);

        // Try to fetch the class config.
        $classconfig = $this->_wims->getclassconfig($this->_qcl, $this->_rcl);
        if ($classconfig == null) {
            return null;
        }

        // Try to fetch the supervisor user config.
        $userconfig = $this->_wims->getuserconfig($this->_qcl, $this->_rcl, "supervisor");
        if ($userconfig == null) {
            return null;
        }

        // Combine the two.
        $result = array_merge($userconfig, $classconfig);

        // Fetch the list of worksheets and add them to the result one by one.
        $result["worksheets"] = array();
        $worksheetids = $this->_wims->getworksheetlist($this->_qcl, $this->_rcl);
        foreach ($worksheetids as $sheetid => $sheetinfo) {
            $sheetconfig = $this->_wims->getworksheetproperties($this->_qcl, $this->_rcl, $sheetid);
            $result["worksheets"][$sheetid] = $sheetconfig;
        }

        // Fetch the list of exams and add them to the result one by one.
        $result["exams"] = array();
        $examids = $this->_wims->getexamlist($this->_qcl, $this->_rcl);
        foreach ($examids as $examid => $examinfo) {
            $examconfig = $this->_wims->getexamproperties($this->_qcl, $this->_rcl, $examid);
            $result["exams"][$examid] = $examconfig;
        }

        return $result;
    }

    /**
     * Update the class config on the WIMS server (if the class exist) (for a given Moodle WIMS module instance)
     * Note that it is valid for this method to be called for classes that have
     * not yet been instantiated on the WIMS server
     *
     * @param object $cm   the course module that the wims class is bound to
     * @param array  $data an associative array of data values
     *
     * @return true on success, null on failure
     */
    public function updateclassconfigformodule($cm, $data) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);

        // Build and apply updated class parameters.
        $classdata = "";
        $classdata .= $this->_dataline($data, "description");
        $classdata .= $this->_dataline($data, "institution");
        $classdata .= $this->_dataline($data, "supervisor");
        $classdata .= $this->_dataline($data, "email");
        $classdata .= $this->_dataline($data, "lang");
        $classdata .= $this->_dataline($data, "expiration");
        if ($classdata != "") {
            $result = $this->_wims->updateclass($this->_qcl, $this->_rcl, $classdata);
            if ($result == null) {
                $this->_wims->debugmsg(__FILE__.':'.__LINE__.': updateclass returning NULL');
                return null;
            }
        }

        // Build and apply updated supervisor parameters.
        $userdata = "";
        $userdata .= $this->_dataline($data, "lastname");
        $userdata .= $this->_dataline($data, "firstname");
        $userdata .= $this->_dataline($data, "email");
        if ($userdata != "") {
            $result = $this->_wims->updateclasssupervisor($this->_qcl, $this->_rcl, $userdata);
            if ($result == null) {
                $this->_wims->debugmsg(__FILE__.':'.__LINE__.': updateclasssupervisor returning NULL');
                return null;
            }
        }

        // Update worksheets.
        foreach ($data["worksheets"] as $sheetid => $sheetconfig) {
            $sheetdata = "";
            foreach ($sheetconfig as $prop => $val) {
                $sheetdata .= $prop.'='.$val."\n";
            }
            if ($sheetdata != "") {
                $result = $this->_wims->updateworksheetproperties($this->_qcl, $this->_rcl, $sheetid, $sheetdata);
                if ($result == null) {
                    $this->_wims->debugmsg(
                        __FILE__.':'.__LINE__.
                        ': updateworksheetproperties returning NULL'
                    );
                    return null;
                }
            }
        }

        // Update exams.
        foreach ($data["exams"] as $examid => $examconfig) {
            $examdata = "";
            foreach ($examconfig as $prop => $val) {
                $examdata .= $prop.'='.$val."\n";
            }
            if ($examdata != "") {
                $result = $this->_wims->updateexamproperties($this->_qcl, $this->_rcl, $examid, $examdata);
                if ($result == null) {
                    $this->_wims->debugmsg(
                        __FILE__.':'.
                        __LINE__.': updateexamproperties returning NULL'
                    );
                    return null;
                }
            }
        }
        return true;
    }

    /**
     * Fetch associative arrays of id=>info for worksheets and exams that compose the given WIMS class
     * Each object in the result has the following fields:
     * - title string containing the item title
     * - the string containing state flag provided by WIMS
     *
     * @param object $cm the course module that the WIMS class is bound to
     *
     * @return array of arrays of objects on success, null on failure
     */
    public function getsheetindex($cm) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);

        // Setup a result object.
        $result = array();

        // Ask WIMS for a list of worksheets.
        $sheetlist = $this->_wims->getworksheetlist($this->_qcl, $this->_rcl);
        if ($sheetlist === null) {
            $this->_wims->debugmsg(__FILE__.':'.__LINE__.': getworksheetlist returning NULL');
            return null;
        }
        $result['worksheets'] = $sheetlist;

        // Ask WIMS for a list of exams.
        $examlist = $this->_wims->getexamlist($this->_qcl, $this->_rcl);
        if ($examlist === null) {
            $this->_wims->debugmsg(__FILE__.':'.__LINE__.': getexamlist returning NULL');
            return null;
        }
        $result['exams'] = $examlist;

        // Return the result object.
        return $result;
    }

    /**
     * Fetch the scores for the given set of worksheets and exams from the given WIMS class
     *
     * @param object $cm             the course module that the WIMS class is bound to
     * @param array  $requiredsheets the identifiers of the exams and worksheets requested (array of array of string)
     *
     * @return array of arrays of objects on success, null on failure
     */
    public function getselectedscores($cm, $requiredsheets) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);

        // Setup a result object.
        $result = array();

        // Iterate over worksheets.
        if (array_key_exists('worksheets', $requiredsheets)) {
            $result['worksheets'] = array();
            foreach ($requiredsheets['worksheets'] as $sheetid) {
                // Ask WIMS for the worksheet scores.
                $sheetdata = $this->_wims->getworksheetscores($this->_qcl, $this->_rcl, $sheetid);
                if (!$sheetdata) {
                    $this->_wims->debugmsg(
                        __FILE__.':'.__LINE__.
                        ': getworksheetscores returning NULL'
                    );
                    return null;
                }
                // Iterate over user score records.
                foreach ($sheetdata as $userscore) {
                    $result['worksheets'][$sheetid][$userscore->id] = floatval($userscore->user_percent) * 0.1;
                }
            }
        }

        // Iterate over exams.
        if (array_key_exists('exams', $requiredsheets)) {
            $result['exams'] = array();
            foreach ($requiredsheets['exams'] as $sheetid) {
                // Ask WIMS for the exam scores.
                $sheetdata = $this->_wims->getexamscores($this->_qcl, $this->_rcl, $sheetid);
                if ($sheetdata) {
                    // Iterate over user score records.
                    foreach ($sheetdata as $userscore) {
                        $result['exams'][$sheetid][$userscore->id] = $userscore->score;
                    }
                } else {
                    // If there is no score yet, $sheetdata can be empty.
                    $this->_wims->debugmsg(
                        __FILE__.':'.__LINE__.
                        ': getexamscores returning NULL'
                    );
                }
            }
        }

        // Return the result object.
        return $result;
    }

    /**
     * Fetch the list of users from the WIMS server (for a given Moodle WIMS module instance)
     *
     * @param object $cm the course module that the wims class is bound to
     *
     * @return array|null array of WIMS login
     */
    public function getuserlist($cm) {

        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);

        // Try to fetch the class config.
        $classconfig = $this->_wims->getclassconfig($this->_qcl, $this->_rcl);
        if ($classconfig == null) {
            return null;
        }

        return $classconfig['userlist'];
    }

    /**
     * Check if a userlogin exist in a WIMS virtual classroom
     *
     * @param object $cm        course module object where to search
     * @param string $wimslogin user to search for
     *
     * @return bool true if user exists in currect WIMS class
     */
    public function checkuser($cm, $wimslogin) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);

        // Check if the user exists within the given course.
        return $this->_wims->checkuser($this->_qcl, $this->_rcl, $login);
    }

    /**
     * Remove all participants and their work in the WIMS classroom associated to $cm
     *
     * @param object $cm course module object
     *
     * @return bool true on success
     */
    public function cleanclass($cm) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);
        // Then ask WIMS to clean the specified classroom.
        return $this->_wims->cleanclass($this->_qcl, $this->_rcl);
    }

    /**
     * Remove one participant and all its work in the WIMS classroom associated to $cm
     *
     * @param object $cm    course module object
     * @param string $quser WIMS user ID to delete
     *
     * @return bool true on success
     */
    public function deluser($cm, $quser) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);
        // Then ask WIMS to clean specified classroom.
        return $this->_wims->deluser($this->_qcl, $this->_rcl, $quser);
    }

    /**
     * Fetch a user data from the WIMS server (for a given Moodle WIMS module instance)
     *
     * @param object $cm    the course module that the wims class is bound to
     * @param string $quser WIMS user ID
     *
     * @return array|null associative array user property values on success or null on fail
     */
    public function getuserdata($cm, $quser) {
        // Start by determining the identifiers for the class.
        $this->_initforcm($cm);

        // Try to fetch the supervisor user config.
        return $this->_wims->getuserconfig($this->_qcl, $this->_rcl, $quser);
    }


    /* ##### Private utility routines ##### */

    /**
     * Private utility routine
     *
     * @param array  $data data
     * @param string $prop prop
     *
     * @return string used by WIMS to set a data value.
     */
    private function _dataline($data, $prop) {
        if (array_key_exists($prop, $data)) {
            return $prop."=".$data[$prop]."\n";
        } else {
            return "";
        }
    }

    /**
     * Private utility routine
     *
     * @param string $login       login
     * @param string $currentlang current lang code
     *
     * @return string $accessurl
     */
    private function _gethomepageurlforlogin($login, $currentlang) {
        // Attempt to create the WIMS session.
        $accessurl = $this->_wims->gethomepageurl($this->_qcl, $this->_rcl, $login, $currentlang);

        // On failure setup the error message.
        if ($accessurl == null) {
            $this->errormsgs = $this->_wims->jsondata->message;
        }

        // Construct the result URL.
        return $accessurl;
    }

    /**
     * Private utility routine
     *
     * @param string $login       login
     * @param string $currentlang current lang code
     *
     * @return string $accessurl
     */
    private function _getscorepageurlforlogin($login, $currentlang) {
        // Attempt to create the WIMS session.
        $accessurl = $this->_wims->getscorepageurl($this->_qcl, $this->_rcl, $login, $currentlang);

        // On failure setup the error message.
        if ($accessurl == null) {
            $this->errormsgs = $this->_wims->jsondata->message;
        }

        // Construct the result URL.
        return $accessurl;
    }

    /**
     * Private utility routine
     *
     * @param string $login       login
     * @param string $currentlang current lang
     * @param string $sheet       sheet id
     *
     * @return string an access URL to log into the worksheet
     */
    private function _getworksheeturlforlogin($login, $currentlang, $sheet) {
        // Attempt to create the WIMS session.
        $accessurl = $this->_wims->getworksheeturl($this->_qcl, $this->_rcl, $login, $currentlang, $sheet);

        // On failure setup the error message.
        if ($accessurl == null) {
            $this->errormsgs = $this->_wims->jsondata->message;
        }

        // Construct the result URL.
        return $accessurl;
    }

    /**
     * Private utility routine
     *
     * @param string $login       login
     * @param string $currentlang current lang code
     * @param string $exam        exam id
     *
     * @return string an access URL to log into the exam
     */
    private function _getexamurlforlogin($login, $currentlang, $exam) {
        // Attempt to create the WIMS session.
        $accessurl = $this->_wims->getexamurl($this->_qcl, $this->_rcl, $login, $currentlang, $exam);

        // On failure setup the error message.
        if ($accessurl == null) {
            $this->errormsgs = $this->_wims->jsondata->message;
        }

        // Construct the result URL.
        return $accessurl;
    }

    /**
     * Private utility routine. Construct connects line
     *
     * @return string used by WIMS to set which server/course couple can access to the WIMS class.
     */
    private function _constructconnectsline() {
        return "connections=+moodlejson/$this->_rcl+ +moodlejsonhttps/$this->_rcl+";
    }

    /**
     * Private utility routine. Initialize the _qcl/_rcl couple.
     *
     * @param object $cm course module object
     *
     * @return void
     */
    private function _initforcm($cm) {
        // Setup the unique WIMS class identifier.
        $this->_qcl = "".($this->_config->qcloffset + $cm->id);
        // Setup the 'owner' identifier (derived from the Moodle class id).
        $this->_rcl = "moodle_$cm->id";
    }
}
