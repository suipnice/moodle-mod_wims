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
     * Can be "OK", "COMMS_FAIL", "NOT_ALLOWED" or "WIMS_FAIL"
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
     * String containing returned message from WIMS server
     *
     * @var string
     */
    public $message;

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
        $this->message = '';
    }

    /**
     * Private utility routine
     * NOTE: We actually expose this method publicly to allow for its use by the wimsinterface class
     *
     * @param string $msg  debug message
     *
     * @return void
     */
    public function debugmsg($msg): void {
        if ($this->debug > 0) {
            if ($this->debugformat == 'html') {
                print("<pre>$msg</pre>\n");
            } else {
                print(" $msg\n");
            }
        }
        // Add this when debugging to redirect debug messages to apache error log: "error_log($msg);".
    }

    /**
     * Private utility routine to execute a call to adm/raw module
     *
     * @param string $job         The WIMS job to execute.
     * @param string $params      optional URL parameters
     *
     * @return void
     */
    private function executeraw($job, $params = ''): void {
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
        $this->debugmsg("\nWIMS Execute: $url");

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
     * Call WIMS API, which respond in JSON
     *
     * @param string $job    The WIMS job to execute.
     * @param string $params optional URL parameters
     * @param bool   $silent make a var_dump or not
     *
     * @return StdClass|null
     */
    private function executejson($job, $params='', $silent=false) {
        // Execute the request, requesting a json format response.
        $this->executeraw($job, $params);
        if ($this->status != 'OK') {
            $this->message = "WIMS execute failed: status = $this->status";
            $this->debugmsg($this->message);
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
        // Some WIMS jobs, like "authuser", don't send back a specific message.
        if (property_exists($this->jsondata, 'message')) {
            $this->message = $this->jsondata->message;
        }

        if ($this->jsondata->status == 'ERROR'
             && $this->jsondata->code == $this->code
             && (strpos($this->message, 'illegal job') !== false)) {
            $this->message = "Your Moodle server is not allowed to do that job on this WIMS server.\
            Ask WIMS admin to allow job '$job'.";
            $this->debugmsg($this->message);
            $this->status = 'NOT_ALLOWED';
            return null;
        }

        if (($this->jsondata->status == 'OK'
                && $this->jsondata->code == $this->code)
            ||
            ($this->jsondata->status == 'ERROR'
                && $this->jsondata->code == $this->code
                && (
                    // In case of modclass/modexam/modsheet/moduser...
                    $this->message == 'nothing done'
                    // In case of checkuser.
                    || strpos($this->message, 'not in this class')
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
                ": WIMS JSON OK response not matched: (for code $this->code):\n".
                "message: ".$this->message
            );
            if ($silent !== true) {
                echo "<div>job:$job - SENDED PARAMS: ".urldecode($params)."</div>";
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
    private function wimsencode($param): string {
        return urlencode(utf8_decode($param));
    }

    /**
     * Connect to the WIMS server and verify that our connection credentials are valid.
     *
     * @return bool true on success
     */
    public function checkident(): bool {
        $this->executejson('checkident');
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
    public function checkclass($qcl, $rcl, $extended=false): ?bool {
        $cmd = ($extended === true) ? 'getclass' : 'checkclass';
        $this->qclass = $qcl;
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $this->executejson($cmd, $params, true);
        return ($this->status == 'OK') ? true : null;
    }

    /**
     * Connect to the WIMS server and verify that a user with the given login exists within the
     * given WIMS course
     *
     * @param string $qcl   the WIMS class identifier (must be an integer with a value > 9999 )
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $login the login of the user (which must respect WIMS user identifier rules)
     * @param bool   $cache if true, don't ask Wims if user already in accessurls[]
     *
     * @return true on success
     */
    public function checkuser($qcl, $rcl, $login, $cache=true): ?bool {
        if ($cache) {
            // If we have already generated an access url for this user then no need to recheck them as they must be OK.
            $fulluserid = $qcl.'/'.$rcl.'/'.$login;
            if (array_key_exists($fulluserid, $this->accessurls)) {
                return true;
            }
        }
        $this->qclass = $qcl;
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&quser='.$login;
        if ($this->executejson('checkuser', $params)) {
            return ($this->jsondata->status == 'OK');
        } else {
            // Communication problem with WIMS server ?
            return null;
        }
    }

    /**
     * Connect to the server and update the course config data
     *
     * @param string $rcl   a unique identifier derived from properties of the MOODLE module
     *                      instance that the WIMS class is bound to
     * @param string $data1 a multi-line text block containing various course-related parameters
     * @param string $data2 a multi-line text block containing various course-creator-related parameters
     *
     * @return bool true on success
     */
    public function addclass($rcl, $data1, $data2): bool {
        $params = 'rclass='.$this->wimsencode($rcl);
        $params .= '&data1='.$this->wimsencode($data1);
        $params .= '&data2='.$this->wimsencode($data2);
        $this->executejson('addclass', $params);
        if ($this->status == 'OK') {
            return $this->class_id;
        } else {
            return false;
        }
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
    public function updateclass($qcl, $rcl, $data1): ?bool {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&data1='.$this->wimsencode($data1);
        $this->executejson('modclass', $params);
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
    public function updateclasssupervisor($qcl, $rcl, $data1): ?bool {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&data1='.$this->wimsencode($data1);
        $params .= '&quser=supervisor';
        $this->executejson('moduser', $params);
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
    public function adduser($qcl, $rcl, $firstname, $lastname, $login): ?bool {
        // Generate a non-useful password.
        $passvalue = rand(1000, 9999);
        $password = "$passvalue$passvalue";

        $data1 = "firstname=".$firstname.
               "\nlastname=".$lastname.
               "\npassword=".$password."\n";
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&quser='.$login;
        $params .= '&data1='.$this->wimsencode($data1);
        $this->executejson('adduser', $params, true);

        // If the user is in classroom's trash, use recuser instead.
        if ($this->jsondata->status == 'ERROR'
            && (strpos($this->message, 'Deleted user found') !== false)) {
            $this->executejson('recuser', $params);
        }

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
    public function gethomepageurl($qcl, $rcl, $login, $currentlang): ?string {
        // If we have already generated an access url for this user then reuse it.
        $fulluserid = $qcl.'/'.$rcl.'/'.$login;
        if (array_key_exists($fulluserid, $this->accessurls)) {
            return $this->accessurls[$fulluserid];
        }
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&quser='.$login;

        $useraddr = $_SERVER['REMOTE_ADDR'];
        // If Moodle is behind a proxy.
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $useraddr = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        $urlparam = '&data1='.$useraddr;

        if (!$this->executejson('authuser', $params.$urlparam)) {
            // Even failed to communicate with the WIMS server,
            // or the user can have started an exam session from another IP and must use the same IP.
            // nb: this can be disabled by teacher in his class).
            return null;
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
    public function getscorepageurl($qcl, $rcl, $login, $currentlang): ?string {
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
    public function getworksheeturl($qcl, $rcl, $login, $currentlang, $sheet): ?string {
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
    public function getexamurl($qcl, $rcl, $login, $currentlang, $exam): ?string {
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
    public function getclassconfig($qcl, $rcl): ?array {
        $this->qclass = $qcl;
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        if ($this->executejson('getclass', $params) === null) {
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
    public function getuserconfig($qcl, $rcl, $login): ?array {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&quser='.$login;
        if ($this->executejson('getuser', $params) === null) {
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
    public function getworksheetlist($qcl, $rcl): ?array {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $jsondata = $this->executejson('listsheets', $params);
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
    public function getworksheetproperties($qcl, $rcl, $sheet): ?array {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&qsheet='.$sheet;
        $jsondata = $this->executejson('getsheet', $params);
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
    public function getworksheetscores($qcl, $rcl, $sheet): ?array {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&qsheet='.$sheet;
        $jsondata = $this->executejson('getsheetscores', $params);
        if ($this->status != 'OK') {
            return null;
        }
        // Should we return also $jsondata->sheet_formula ?
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
    public function updateworksheetproperties($qcl, $rcl, $sheet, $data1): ?bool {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&qsheet='.$sheet;
        $params .= '&data1='.$this->wimsencode($data1);
        $this->executejson('modsheet', $params);
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
    public function getexamlist($qcl, $rcl): ?array {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $jsondata = $this->executejson('listexams', $params);
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
    public function getexamproperties($qcl, $rcl, $exam): ?array {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&qexam='.$exam;
        $jsondata = $this->executejson('getexam', $params);
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
    public function getexamscores($qcl, $rcl, $exam): ?array {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&qexam='.$exam;
        $jsondata = $this->executejson('getexamscores', $params);
        if ($this->status != 'OK') {
            $this->debugmsg("getexamscores: ".$jsondata->message);
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
    public function updateexamproperties($qcl, $rcl, $exam, $data1): ?bool {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&qexam='.$exam;
        $params .= '&data1='.$this->wimsencode($data1);
        $this->executejson('modexam', $params);
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
    public function cleanclass($qcl, $rcl): bool {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $this->executejson('cleanclass', $params);

        if ($this->status == 'OK') {
            // Reset eventual previously accessed url in this session.
            $this->accessurls = array();
            return true;
        } else {
            return false;
        }
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
    public function deluser($qcl, $rcl, $quser): bool {
        $params = 'qclass='.$qcl.'&rclass='.$this->wimsencode($rcl);
        $params .= '&quser='.$quser;
        $this->executejson('deluser', $params);
        if ($this->status == 'OK') {
            // Reset eventual previously accessed url in this session.
            $fulluserid = $qcl.'/'.$rcl.'/'.$quser;
            if (array_key_exists($fulluserid, $this->accessurls)) {
                unset($this->accessurls[$fulluserid]);
            }
            return true;
        } else {
            return false;
        }
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
    public function getscore($qcl, $rcl, $quser): array {
        $params  = "qclass=".$qcl."&rclass=".$this->wimsencode($rcl);
        $params .= "&quser=".$quser;
        if ($this->executejson("getscore", $params) === null) {
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
        // this primitive does not reply 'OK' at the first line so we call executeraw() and not executejson()
        $this->executeraw("help");
        return $this->data;
    }

    public function addsheet($qcl, $rcl, $contents="", $sheetmode="0", $title="", $description="", $expiration=""){
        $contents = str_replace("\n", ";", $contents);
        $params = "qclass=".$qcl."&rclass=".$this->wimsencode($rcl);
        $data1 = "";
        if ($title != "")       $data1.= "title=$title\n";
        if ($description != "") $data1.= "description=$description\n";
        if ($expiration != "")  $data1.= "expiration=$expiratiion\n";
        if ($contents != "")    $data1.= "contents=$contents\n";
        if ($sheetmode != "0")  $data1.= "sheetmode=$sheetmode\n";
        $params.= "&data1=".$this->wimsencode($data1);
        return $this->executejson("addsheet", $params);
    }


    public function getcsv($qcl, $rcl, $option=""){
        $params = "qclass=".$qcl."&rclass=".$this->wimsencode($rcl);
        $params.= "&option=".$this->wimsencode($option)."&format=tsv";

        // this primitive does not reply 'OK' at the first line, since it's designed
        // to output a valid csv file so we call executeraw() and not executejson()
        $this->executeraw("getcsv", $params);
        return $this->data;
    }
    */
}
