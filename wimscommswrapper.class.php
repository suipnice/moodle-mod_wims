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
 * Low level communication library for interfacing to a WIMS server
 *
 * @author    Sadge <daniel@edunao.com>
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   mod_wims
 */


/* wims_comms_wrapper
 * The class wims_comms_wrapper implements the protocol described at
 * http://wims.unice.fr/wims/?module=adm/raw&job=help
 *
 * initialisation: wims_comms_wrapper($wimscgiurl,$servicepass)
 * parameters:
 *   $wimscgiurl is the URL of the Wims server
 *   $servicepass is the value of the 'ident_password' field in the WIMS configuration files (see README for more details)
 */

namespace mod_wims;
defined('MOODLE_INTERNAL') || die;

use \stdClass;

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
class wims_comms_wrapper {
    /**
     * URL to the wims server.
     *
     * @var string
     */
    public $wimsurl;

    /**
     * Protocol (http or https), extracted from $wimsurl.
     *
     * @var string
     */
    public $protocolmodifier;

    /**
     * The password required for us to connect
     *
     * @var string
     */
    public $servicepass;

    /**
     * Default:0
     *
     * @var int
     */
    public $debug;

    /**
     * WIMS raw response
     *
     * @var string
     */
    public $rawdata;

    /**
     * Querried WIMS class id
     *
     * @var string
     */
    public $qclass;

    /**
     * Can be "OK", "COMMS_FAIL", or "WIMS_FAIL"
     *
     * @var string
     */
    public $status;

    /**
     * A random string used to match response with its request
     *
     * @var string
     */
    public $code;

    /**
     * True if $allowselfsignedcertificates=false
     *
     * @var bool
     */
    public $sslverifypeer;

    /**
     * Associative array of access urls keyed by user id.
     *
     * @var array
     */
    public $accessurls;

    /**
     * String indicating in which format (html / plain text) debug must be formatted in
     *
     * @var string
     */
    public $debugformat;

    /**
     * Ctor (the class constructor)
     * stores away the supplied parameters but performs no actions
     *
     * @param string $wimscgiurl                  the URL to the wims server
     * @param string $servicepass                 the password required for us to connect
     *                                            (see ident_password field in the .../moodlejson file
     *                                            described in wimsinterface.class.php)
     * @param bool   $allowselfsignedcertificates true if self signed certificates are allowed.
     * @param string $debugformat                 indicates if debug must be formatted in HTML or plain text
     *
     * @return void
     */
    public function __construct($wimscgiurl, $servicepass, $allowselfsignedcertificates=false, $debugformat='html') {
        $this->wimsurl = $wimscgiurl;
        $this->protocolmodifier = (substr($wimscgiurl, 0, 5) == 'https') ? 'https' : '';
        $this->servicepass = $servicepass;
        $this->qclass = '';
        $this->debug = 0;
        $this->status = 'OK';
        $this->code = '';
        $this->sslverifypeer = ($allowselfsignedcertificates == false) ? true : false;
        $this->accessurls = array();
        $this->debugformat = $debugformat;
    }

    /**
     * Private utility routine
     * NOTE: We actually expose this method publicly to allow for its use by the wimsinterface class
     *
     * @param string $msg  debug message
     *
     * @return void
     */
    public function debugmsg($msg) {
        if ($this->debug > 0) {
            if ($this->debugformat == 'html') {
                print("<pre>$msg</pre>\n");
            } else {
                print(" $msg\n");
            }
        }
        // The following line can be uncommented when debugging to redirect debug messages to apache error log.
        /* error_log($msg); */
    }

    /**
     * Private utility routine to execute a call to adm/raw module
     *
     * @param string $job         The WIMS job to execute.
     * @param string $params      optional URL parameters
     *
     * @return void
     */
    private function _executeraw($job, $params = '') {
        // Reset the status code to 'OK' here as a smart place to allow either coms or subsequent logic to reset to error condition.
        $this->status = 'OK';

        // Choose a random request id (for keeping consistency with the WIMS response).
        $code = rand(100, 999);
        $this->code = "$code";

        // Setup the service name value, applying 'https' suffix if required.
        $service = 'moodlejson'.$this->protocolmodifier;

        // Construct the core URL.
        $url = $this->wimsurl."?module=adm/raw&job=".$job."&code=".$this->code."&ident=".$service."&passwd=".$this->servicepass;

        // Add URL parameters (if any).
        if (strlen($params) > 0) {
            $url .= '&'.$params;
        }

        // If we're debuggin then log the event.
        $this->debugmsg("WIMS Execute: $url");

        // Initialise cURL resource.
        $curl = curl_init();

        // Set some cURL options.
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'Moodle',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FAILONERROR => true,
            CURLOPT_SSL_VERIFYPEER => $this->sslverifypeer
        ));

        // Send the request & save response to $resp.
        $curlresult = curl_exec($curl);

        // Check whether the fetch succeeded or not.
        if (!$curlresult) {
            $curlerrorno = curl_errno($curl);
            $curlerror = curl_error($curl);
            $this->lines = explode("\n", "Error while fetching URL: $url\nError $curlerrorno: $curlerror");
            $this->status = 'COMMS_FAIL';
            $this->debugmsg("WIMS comms error: $this->rawdata");
        } else {
            $this->status = 'OK';
            $this->rawdata = utf8_encode($curlresult);
            $this->debugmsg('WIMS comms success');
        }

        // Housekeeping.
        curl_close($curl);
    }

    /**
     * Private utility routine, to handle request in old wims format
     *
     * @param string $job  The WIMS job to execute.
     * @param string $params optional URL parameters
     *
     * @return void
     */
    private function _executewims($job, $params='') {
        $this->_executeraw($which, $params);
        if ($this->status == 'OK') {
            $this->linedata = explode("\n", $this->rawdata);
        }
    }

    /**
     * Call WIMS API, which respond in JSON
     *
     * @param string $job    The WIMS job to execute.
     * @param string $params optional URL parameters
     * @param bool   $silent make a var_dump or not
     *
     * @return StdClass|null
     */
    private function _executejson($job, $params='', $silent=false) {
        // Execute the request, requesting a json format response.
        $this->_executeraw($job, $params);
        if ($this->status != 'OK') {
            $this->debugmsg("WIMS execute failed: status = $this->status");
            return null;
        }

        // If the request went through ok (ie if the HTTP GET request succeeded)
        // then parse json data and make sure that it contains a Status=>OK.
        $this->jsondata = json_decode($this->rawdata);
        if (!$this->jsondata) {
            echo "<pre>\nERROR Invalid JSON response to WIMS request: ".$job."\n".$this->rawdata."\n</pre>";
            $hmp = "";
            for ($i = 0; $i < strlen($this->rawdata); ++$i) {
                $hmp .= '/'.ord($this->rawdata[$i]);
            }
            throw new Exception('WIMS server returned invalid JSON: $job:'.$this->rawdata);
        }

        if (($this->jsondata->status == 'OK'
                && $this->jsondata->code == $this->code)
            ||
            ($this->jsondata->status == 'ERROR'
                && $this->jsondata->code == $this->code
                && (
                    // In case of modclass/modexam/modsheet/moduser...
                    $this->jsondata->message == 'nothing done'
                    // In case of checkuser.
                    || strpos($this->jsondata->message, 'not in this class')
                    )
                )
        ) {
            // Done!
            $this->debugmsg("JSON: status = OK");
            // Copy the json data to an array and remove entries that are not pertinent.
            $this->arraydata = (array)$this->jsondata;
            $badkeys = array("code", "job");
            foreach ($badkeys as $key) {
                unset($this->arraydata[$key]);
            }
            return (object) $this->arraydata;
        } else {
            $this->status = 'WIMS_FAIL';
            $this->debugmsg(
                "ERROR: ".__FILE__.":".__LINE__.
                ": WIMS JSON OK response not matched: (for code $this->code):\n"
            );
            if ($silent !== true) {
                echo "<div>SENDED PARAMS: ".urldecode($params)."</div>";
                var_dump($this->jsondata);
            }
            return null;
        }
    }

    /**
     * Private utility routine
     *
     * @param string $param parameters to be url encoded
     *
     * @return string urlencoded param
     */
    private function _wimsencode($param) {
        return urlencode(utf8_decode($param));
    }

    /**
     * Connect to the WIMS server and verify that our connection credentials are valid.
     *
     * @return bool true on success
     */
    public function checkident() {
        $this->_executejson('checkident');
        return ($this->status == 'OK');
    }

    /**
     * Connect to the WIMS server and verify that our connection credentials are valid and
     * that a class with id $qcl exists and is accessible to us
     *
     * @param string  $qcl      the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string  $rcl      a unique identifier derived from properties of the MOODLE module
     *                          instance that the WIMS class is bound to
     * @param boolean $extended if true uses getclass call instead of checkclass call to verify
     *                          not only existence of class but also service access rights
     *
     * @return true on success, null on failure)
     */
    public function checkclass($qcl, $rcl, $extended=false) {
        $cmd = ($extended === true) ? 'getclass' : 'checkclass';
        $silent = ($extended === true) ? true : null;
        $this->qclass = $qcl;
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $this->_executejson($cmd, $params, $silent);
        return ($this->status == 'OK') ? true : null;
    }

    /**
     * Connect to the WIMS server and verify that a user with the given login exists within the
     * given WIMS course
     *
     * @param string $qcl   the WIMS class identifier (must be WIMS_FAIL an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $login the login of the user (which must respect WIMS user identifier rules)
     *
     * @return true on success
     */
    public function checkuser($qcl, $rcl, $login) {
        // If we have already generated an access url for this user then no need to recheck them as they must be OK.
        $fulluserid = $qcl.'/'.$rcl.'/'.$login;
        if (array_key_exists($fulluserid, $this->accessurls)) {
            return true;
        }
        $this->qclass = $qcl;
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&quser='.$login;
        $this->_executejson('checkuser', $params);
        return ($this->jsondata->status == 'OK');
    }

    /**
     * Connect to the server and update the course config data
     *
     * @param string $qcl   the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $data1 a multi-line text block containing various course-related parameters
     * @param string $data2 a multi-line text block containing various course-creator-related parameters
     *
     * @return bool true on success
     */
    public function addclass($qcl, $rcl, $data1, $data2) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&data1='.$this->_wimsencode($data1);
        $params .= '&data2='.$this->_wimsencode($data2);
        $this->_executejson('addclass', $params);
        return ($this->status == 'OK');
    }

    /**
     * Connect to the server and attempt to instantiate a new WIMS course with the given parameters
     *
     * @param string $qcl   the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $data1 a multi-line text block containing various course-related parameters
     *
     * @return true on success, null on failure
     */
    public function updateclass($qcl, $rcl, $data1) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&data1='.$this->_wimsencode($data1);
        $this->_executejson('modclass', $params);
        return ($this->status == 'OK') ? true : null;
    }

    /**
     * Connect to the server and update the supervisor properties for the given course
     *
     * @param string $qcl   the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $data1 a multi-line text block containing various user parameters
     *
     * @return bool|null true on success, null on failure
     */
    public function updateclasssupervisor($qcl, $rcl, $data1) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&data1='.$this->_wimsencode($data1);
        $params .= '&quser=supervisor';
        $this->_executejson('moduser', $params);
        return ($this->status == 'OK') ? true : null;
    }

    /**
     * Connect to the server and attempt to instantiate a new WIMS user within an existing WIMS course
     *
     * @param string $qcl       the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl       a unique identifier derived from properties of the MOODLE module
     *                          instance that the WIMS class is bound to
     * @param string $firstname the user's first name
     * @param string $lastname  the user's last name
     * @param string $login     the user's login (sometimes refered to as their user name)
     *
     * @return bool|null true on success, null on failure
     */
    public function adduser($qcl, $rcl, $firstname, $lastname, $login) {
        // Generate a non-useful password.
        $passvalue = rand(1000, 9999);
        $password = "$passvalue$passvalue";

        $data1 = "firstname=".$firstname.
               "\nlastname=".$lastname.
               "\npassword=".$password."\n";
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&quser='.$login;
        $params .= '&data1='.$this->_wimsencode($data1);
        $this->_executejson('adduser', $params);
        return ($this->status == 'OK') ? true : null;
    }

    /**
     * Connect to the server and attempt to instantiate a new session connecting the given user to the WIMS course home page
     *
     * @param string $qcl         the WIMS class identifier (must be an integer with a value > 9999)
     * @param string $rcl         a unique identifier derived from properties of the MOODLE module
     *                            instance that the WIMS class is bound to
     * @param string $login       the user's login (sometimes refered to as their user name)
     * @param string $currentlang Language
     *
     * @return string|null fully qualified connection url on success, null on failure
     */
    public function gethomepageurl($qcl, $rcl, $login, $currentlang) {
        // If we have already generated an access url for this user then reuse it.
        $fulluserid = $qcl.'/'.$rcl.'/'.$login;
        if (array_key_exists($fulluserid, $this->accessurls)) {
            return $this->accessurls[$fulluserid];
        }
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&quser='.$login;
        $urlparam = '&data1='.$_SERVER['REMOTE_ADDR'];
        $this->_executejson('authuser', $params.$urlparam);
        if ($this->status == 'COMMS_FAIL') {
            // Failed to communcate witht he wims server so there's no possibility of recovery.
            return null;
        } else if ($this->status == 'WIMS_FAIL') {
            // Check for a recoverable failed attempt case.
            $matches = array();
            $matched = preg_match('/.*IP \(([0123456789.]*) !=.*/', $this->jsondata->message, $matches);
            if (($matched !== 1) || (count($matches) !== 2)) {
                // The error message doesn't match our regex so give up.
                $this->debugmsg('authuser failed - and regex not matched so give up without retry');
                return null;
            }
            $this->debugmsg(
                'authuser - retrying after first refusal => applying URL '.
                $matches[1].' FROM '.$this->jsondata->message
            );
            // Our error message did match the regex so try again, substituting in the deducd IP address.
            $urlparam = '&data1='.$matches[1];
            $this->_executejson('authuser', $params.$urlparam);
            if ($this->status != 'OK') {
                // OK so after a second attempt we've still failed. Time to call it a day!
                return null;
            }
        }
        // Store away the generated url and return it.
        $this->accessurls[$fulluserid] = $this->jsondata->home_url;
        return $this->accessurls[$fulluserid].'&lang='.$currentlang;
    }

    /**
     * Connect to the server and attempt to instantiate a new session
     * connecting the given user to their score management page in the WIMS course
     *
     * @param string $qcl         the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl         a unique identifier derived from properties of the MOODLE module
     *                            instance that the WIMS class is bound to
     * @param string $login       the user's login (sometimes refered to as their user name)
     * @param string $currentlang Language
     *
     * @return string|null fully qualified connection url on success, null on failure
     */
    public function getscorepageurl($qcl, $rcl, $login, $currentlang) {
        $url = $this->gethomepageurl($qcl, $rcl, $login, $currentlang);
        if ($url == null) {
            return null;
        }
        return $url.'&module=adm/class/userscore';
    }

    /**
     * Connect to the server and attempt to instantiate a new session
     * connecting the given user to a given worksheet page of the WIMS course
     *
     * @param string $qcl         the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl         a unique identifier derived from properties of the MOODLE module
     *                            instance that the WIMS class is bound to
     * @param string $login       the user's login (sometimes refered to as their user name)
     * @param string $currentlang Language
     * @param string $sheet       the identifier of the worksheet to connect to
     *
     * @return string|null fully qualified connection url on success, null on failure
     */
    public function getworksheeturl($qcl, $rcl, $login, $currentlang, $sheet) {
        $url = $this->gethomepageurl($qcl, $rcl, $login, $currentlang);
        if ($url == null) {
            return null;
        }
        return $url.'&module=adm/sheet&sh='.$sheet;
    }

    /**
     * Connect to the server and attempt to instantiate a new session
     * connecting the given user to a given worksheet page of the WIMS course
     *
     * @param string $qcl         the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl         a unique identifier derived from properties of the MOODLE module
     *                            instance that the WIMS class is bound to
     * @param string $login       the user's login (sometimes refered to as their user name)
     * @param string $currentlang Language
     * @param string $exam        the identifier of the exam to connect to
     *
     * @return string|null fully qualified connection url on success, null on failure
     */
    public function getexamurl($qcl, $rcl, $login, $currentlang, $exam) {
        $url = $this->gethomepageurl($qcl, $rcl, $login, $currentlang);
        if ($url == null) {
            return null;
        }
        return $url.'&module=adm/class/exam&exam='.$exam;
    }

    /**
     * Connect to the server and attempt to retrieve the configuration data for a class
     *
     * @param string $qcl the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl a unique identifier derived from properties of the MOODLE module
     *                    instance that the WIMS class is bound to
     *
     * @return array|null result as array of lines on success, null on failure
     */
    public function getclassconfig($qcl, $rcl) {
        $this->qclass = $qcl;
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        if ($this->_executejson('getclass', $params) === null) {
            return null;
        }
        // Remove entries that are not pertinent.
        $badkeys = array("status", "query_class", "rclass", "password");
        foreach ($badkeys as $key) {
            unset($this->arraydata[$key]);
        }
        return $this->arraydata;
    }

    /**
     * Connect to the server and attempt to retrieve the configuration data for a user
     *
     * @param string $qcl   the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $login the user's login (sometimes refered to as their user name)
     *
     * @return array|null result as array of lines on success, null on failure
     */
    public function getuserconfig($qcl, $rcl, $login) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&quser='.$login;
        if ($this->_executejson('getuser', $params) === null) {
            return null;
        }
        // Remove entries that are not pertinent.
        $badkeys = array("status", "query_class", "queryuser");
        foreach ($badkeys as $key) {
            unset($this->arraydata[$key]);
        }
        return $this->arraydata;
    }

    /**
     * Connect to the server and attempt to retrieve the list of worksheets for a given class
     *
     * @param string $qcl the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl a unique identifier derived from properties of the MOODLE module
     *                    instance that the WIMS class is bound to
     *
     * @return array|null array of sheet description objects on success, null on failure
     */
    public function getworksheetlist($qcl, $rcl) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $jsondata = $this->_executejson('listsheets', $params);
        if ($this->status != 'OK') {
            return null;
        }
        $result = array();
        for ($idx = 0; $idx < $jsondata->nbsheet; ++$idx) {
            $id = $jsondata->sheetlist[$idx];
            $rawtitle = $jsondata->sheettitlelist[$idx];
            $titleparts = explode(':', $rawtitle);
            $sheet = new StdClass;
            $sheet->title = trim($titleparts[1]);
            $sheet->state = trim($titleparts[2]);
            $result[$id] = $sheet;
        }
        return $result;
    }

    /**
     * Connect to the server and attempt to retrieve properties of a given worksheet for a given class
     *
     * @param string $qcl   the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $sheet the WIMS worksheet identifier
     *
     * @return array|null associative array of properties on success, null on failure
     */
    public function getworksheetproperties($qcl, $rcl, $sheet) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&qsheet='.$sheet;
        $jsondata = $this->_executejson('getsheet', $params);
        if ($this->status != 'OK') {
            return null;
        }
        $this->sheetprops = array();
        $this->sheetprops["status"]      = $jsondata->sheet_status;
        $this->sheetprops["expiration"]  = $jsondata->sheet_expiration;
        $this->sheetprops["title"]       = $jsondata->sheet_title;
        $this->sheetprops["description"] = $jsondata->sheet_description;
        return $this->sheetprops;
    }

    /**
     * Connect to the server and attempt to retrieve scores of a given worksheet for a given class
     *
     * @param string $qcl   the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $sheet the WIMS worksheet identifier
     *
     * @return array|null array of score records on success, null on failure
     */
    public function getworksheetscores($qcl, $rcl, $sheet) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&qsheet='.$sheet;
        $jsondata = $this->_executejson('getsheetscores', $params);
        if ($this->status != 'OK') {
            return null;
        }
        return $jsondata->data_scores;
    }

    /**
     * Connect to the server and attempt to update properties for the given worksheet of the given class
     *
     * @param string $qcl   the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $sheet the WIMS worksheet identifier
     * @param string $data1 a multi-line text block containing various course-related parameters
     *
     * @return bool|null true on success, null on failure
     */
    public function updateworksheetproperties($qcl, $rcl, $sheet, $data1) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&qsheet='.$sheet;
        $params .= '&data1='.$this->_wimsencode($data1);
        $this->_executejson('modsheet', $params);
        return ($this->status == 'OK') ? true : null;
    }

    /**
     * Connect to the server and attempt to retrieve the list of exams for a given class
     *
     * @param string $qcl the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl a unique identifier derived from properties of the MOODLE module
     *                    instance that the WIMS class is bound to
     *
     * @return array|null array of identifiers on success, null on failure
     */
    public function getexamlist($qcl, $rcl) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $jsondata = $this->_executejson('listexams', $params);
        if ($this->status != 'OK') {
            return null;
        }
        $result = array();
        for ($idx = 0; $idx < $jsondata->nbexam; ++$idx) {
            $id = $jsondata->examlist[$idx];
            $rawtitle = $jsondata->examtitlelist[$idx];
            $titleparts = explode(':', $rawtitle);
            $exam = new StdClass;
            $exam->title = trim($titleparts[1]);
            $exam->state = trim($titleparts[2]);
            $result[$id] = $exam;
        }
        return $result;
    }

    /**
     * Connect to the server and attempt to retrieve properties of a given exam for a given class
     *
     * @param string $qcl  the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl  a unique identifier derived from properties of the MOODLE module
     *                     instance that the WIMS class is bound to
     * @param string $exam the WIMS exam identifier
     *
     * @return array|null associative array of properties on success, null on failure
     */
    public function getexamproperties($qcl, $rcl, $exam) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&qexam='.$exam;
        $jsondata = $this->_executejson('getexam', $params);
        if ($this->status != 'OK') {
            return null;
        }
        $this->examprops = array();
        $this->examprops['opening']     = $jsondata->exam_opening;
        $this->examprops['status']      = $jsondata->exam_status;
        $this->examprops['duration']    = $jsondata->exam_duration;
        $this->examprops['attempts']    = $jsondata->exam_attempts;
        $this->examprops['title']       = $jsondata->exam_title;
        $this->examprops['description'] = $jsondata->exam_description;
        $this->examprops['cut_hours']   = $jsondata->exam_cut_hours;
        // Treat both the badly formed and correctly formed properties here to avoid problems with different wims versions.
        if (property_exists($jsondata, 'exam_expiration')) {
            $this->examprops['expiration']  = $jsondata->exam_expiration;
        } else if (property_exists($jsondata, 'exam_expiration ')) {
            $prop = 'exam_expiration ';
            $this->examprops["expiration"]  = $jsondata->$prop;
        }
        return $this->examprops;
    }

    /**
     * Connect to the server and attempt to retrieve scores of a given exam for a given class
     *
     * @param string $qcl  the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl  a unique identifier derived from properties of the MOODLE module
     *                     instance that the WIMS class is bound to
     * @param string $exam the WIMS exam identifier
     *
     * @return array|null array of score records on success, null on failure
     */
    public function getexamscores($qcl, $rcl, $exam) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&qexam='.$exam;
        $jsondata = $this->_executejson('getexamscores', $params);
        if ($this->status != 'OK') {
            $this->_wims->debugmsg("getexamscores: ".$jsondata->message);
            return null;
        }
        return $jsondata->data_scores;
    }

    /**
     * Connect to the server and attempt to update properties for the given exam of the given class
     *
     * @param string $qcl   the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $exam  the WIMS exam identifier
     * @param string $data1 a multi-line text block containing various course-related parameters
     *
     * @return bool|null true on success, null on failure
     */
    public function updateexamproperties($qcl, $rcl, $exam, $data1) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&qexam='.$exam;
        $params .= '&data1='.$this->_wimsencode($data1);
        $this->_executejson('modexam', $params);
        return ($this->status == 'OK') ? true : null;
    }

    /**
     * Remove all participants and their work in the $qcl WIMS classroom
     *
     * @param string $qcl The WIMS class identifier
     * @param string $rcl An unique identifier derived from properties of the Moodle module instance
     *
     * @return bool true on success
     */
    public function cleanclass($qcl, $rcl) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $this->_executejson('cleanclass', $params);
        return ($this->status == 'OK');
    }

    /**
     * Remove one participant and its work in the $qcl WIMS classroom
     *
     * @param string $qcl The WIMS class identifier
     * @param string $rcl An unique identifier derived from properties of the Moodle module instance
     * @param string $quser WIMS user ID to be deleted
     *
     * @return bool true on success
     */
    public function deluser($qcl, $rcl, $quser) {
        $params = 'qclass='.$qcl.'&rclass='.$this->_wimsencode($rcl);
        $params .= '&quser='.$quser;
        $this->_executejson('deluser', $params);
        return ($this->status == 'OK');
    }

    /**
     * Get scores of one user in a WIMS classroom
     *
     * @param string $qcl   The WIMS class identifier
     * @param string $rcl   An unique identifier derived from properties of the Moodle module instance
     * @param string $quser WIMS user ID to be deleted
     *
     * @return array scores of $quser user in $qcl classroom
     */
    public function getscore($qcl, $rcl, $quser) {
        $params  = "qclass=".$qcl."&rclass=".$this->_wimsencode($rcl);
        $params .= "&quser=".$quser;
        if ($this->_executejson("getscore", $params) === null) {
            return array("status" => "ERROR", "message" => "getscore returned null");
        } else {
            // Remove entries that are not pertinent.
            $badkeys = array("status", "query_class", "query_user");
            foreach ($badkeys as $key) {
                unset($this->arraydata[$key]);
            }
            return $this->arraydata;
        }

    }

    /*
     NOTE: The following methods has been tested by Sadge and shown to work but are not required by wimsinterface.class.php
      and so has been commented out

    public function help(){
        // this primitive does not reply 'OK' at the first line so we call _executeraw() and not _executejson()
        $this->_executeraw("help");
        return $this->data;
    }

    public function addsheet($qcl, $rcl, $contents="", $sheetmode="0", $title="", $description="", $expiration=""){
        $contents = str_replace("\n", ";", $contents);
        $params = "qclass=".$qcl."&rclass=".$this->_wimsencode($rcl);
        $data1 = "";
        if ($title != "")       $data1.= "title=$title\n";
        if ($description != "") $data1.= "description=$description\n";
        if ($expiration != "")  $data1.= "expiration=$expiratiion\n";
        if ($contents != "")    $data1.= "contents=$contents\n";
        if ($sheetmode != "0")  $data1.= "sheetmode=$sheetmode\n";
        $params.= "&data1=".$this->_wimsencode($data1);
        return $this->_executejson("addsheet", $params);
    }


    public function getcsv($qcl, $rcl, $option=""){
        $params = "qclass=".$qcl."&rclass=".$this->_wimsencode($rcl);
        $params.= "&option=".$this->_wimsencode($option)."&format=tsv";

        // this primitive does not reply 'OK' at the first line, since it's designed
        // to output a valid csv file so we call _executeraw() and not _executejson()
        $this->_executeraw("getcsv", $params);
        return $this->data;
    }
    */
}
