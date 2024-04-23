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
 * Instance configuration formula for setting up new instances of the module
 *
 * @package   mod_wims
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @author    Sadge <daniel@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/wims/wimsinterface.class.php');
use mod_wims\wims_interface;

/**
 * Mod wims mod form class
 *
 * @category  form
 * @package   mod_wims
 * @author    Sadge <daniel@edunao.com>
 * @copyright 2015 Edunao SAS <contact@edunao.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link      https://github.com/suipnice/moodle-mod_wims
 */
class mod_wims_mod_form extends moodleform_mod {
    /**
     * __construct
     *
     * @param mixed $current Current data
     * @param int   $section Section of course that module instance will be put in or is in
     * @param mixed $cm      course module object
     * @param mixed $course  Current course.
     *
     * @return void
     */
    public function __construct($current, $section, $cm, $course) {
        // Store away properties that we may need later.
        $this->cm = $cm;
        $this->course = $course;
        // Delegate to parent.
        parent::__construct($current, $section, $cm, $course);
        // Setup a global for use in the event handler that catches the module rename event.
        global $wimsmodform;
        $wimsmodform = true;
    }

    /**
     * Add text field
     *
     * @param unknown $fieldnamebase field name base
     * @param unknown $maxlen        max len
     * @param unknown $defaultvalue  default value
     * @param unknown $fieldsuffix   field suffix
     *
     * @return void
     */
    private function addtextfield($fieldnamebase, $maxlen, $defaultvalue = null, $fieldsuffix = ''): void {
        $mform = $this->_form;
        $fieldname = $fieldnamebase . $fieldsuffix;
        $mform->addElement('text', $fieldname, get_string($fieldnamebase, 'wims'), ['size' => '60']);
        $mform->setType($fieldname, PARAM_TEXT);
        $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->addRule($fieldname, get_string('maximumchars', '', $maxlen), 'maxlength', $maxlen, 'client');
        if ($defaultvalue) {
            $mform->setDefault($fieldname, $defaultvalue);
        }
    }

    /**
     * Add textarea field (currently unsed here ?)
     *
     * @param unknown $fieldnamebase field name base
     * @param unknown $defaultvalue  default value
     * @param unknown $fieldsuffix   field suffix
     *
     * @return void
     */
    private function addtextareafield($fieldnamebase, $defaultvalue = null, $fieldsuffix = ''): void {
        $mform = $this->_form;
        $fieldname = $fieldnamebase . $fieldsuffix;
        $mform->addElement('textarea', $fieldname, get_string($fieldnamebase, 'wims'), ['cols' => '60', 'rows' => '5']);
        $mform->setType($fieldname, PARAM_TEXT);
        if ($defaultvalue) {
            $mform->setDefault($fieldname, $defaultvalue);
        }
    }

    /**
     * Add check box
     *
     * @param unknown $fieldnamebase field name base
     * @param unknown $defaultvalue  default value
     * @param unknown $fieldsuffix   field suffix
     *
     * @return void
     */
    private function addcheckbox($fieldnamebase, $defaultvalue = null, $fieldsuffix = ''): void {
        $mform = $this->_form;
        $fieldname = $fieldnamebase . $fieldsuffix;
        $mform->addElement('checkbox', $fieldname, get_string($fieldnamebase, 'wims'));
        $mform->setType($fieldname, PARAM_TEXT);
        if ($defaultvalue) {
            $mform->setDefault($fieldname, $defaultvalue);
        }
    }

    /**
     * Defines forms elements
     *
     * @return void
     */
    public function definition(): void {

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Text fields.
        $this->addtextfield('name', 255);
        $this->addtextfield('userfirstname', 63);
        $this->addtextfield('userlastname', 63);
        $this->addtextfield('useremail', 255);
        $this->addtextfield('userinstitution', 127);

        $this->standard_coursemodule_elements();
    }

    /**
     * Definition after data
     *
     * @return void
     */
    public function definition_after_data(): void {
        parent::definition_after_data();
        $mform =& $this->_form;

        // If we have data from WIMS then use it.
        if (property_exists($this, 'configfromwims') === true) {
            // Treat all of the worksheets and then all of the exams.
            foreach (["worksheets", "exams"] as $sheettype) {
                $sheettypestr = get_string('sheettype' . $sheettype, 'wims');

                // For each sheet (whether worksheet or exam).
                foreach ($this->configfromwims[$sheettype] as $sheetidx => $sheetprops) {
                    // Work out the sheet status.
                    switch ($sheetprops['status']) {
                        case '1':
                            // Active.
                            $statusstr = '';
                            break;
                        case '2':
                            // Expired.
                            $statusstr = get_string('wimsstatus2', 'wims');
                            break;
                        default:
                            // Inactive.
                            $statusstr = get_string('wimsstatusx', 'wims');
                            break;
                    }
                    if ($statusstr !== '') {
                        $statusstr = ' [ ' . $statusstr . ' ] ';
                    }
                    // Split the 'graded' flag out from the title (if there is one).
                    $fulltitle = trim($sheetprops['title']);
                    if (substr($fulltitle, -1) == '*') {
                        $title = trim(substr($fulltitle, 0, -1));
                        $graded = '1';
                    } else {
                        $title = $fulltitle;
                        $graded = '0';
                    }

                    // Open a dedicated section for each sheet.
                    $headerstr = $sheettypestr . " " . $title . $statusstr;
                    $mform->addElement('header', 'sheetheader' . $sheettype . $sheetidx, $headerstr);

                    // Add title and 'graded' checkbox.
                    $this->addtextfield('sheettitle', 255, $title, $sheettype . $sheetidx);
                    if ($sheettype != 'exams') {
                        $this->addcheckbox('sheetgraded', $graded, $sheettype . $sheetidx);
                    }

                    // Add an expiry date field.
                    $datestr = $sheetprops['expiration'];
                    $dateobj = new DateTime($datestr, new DateTimeZone('UTC'));
                    $dateval = $dateobj->getTimestamp();
                    $expiryfieldname = 'sheetexpiry' . $sheettype . $sheetidx;
                    $mform->addElement(
                        'date_selector',
                        $expiryfieldname,
                        get_string('sheetexpiry', 'wims'),
                        ['timezone' => 'UTC']
                    );
                    $mform->setDefault($expiryfieldname, $dateval);
                }
            }
        }

        $this->add_action_buttons();
    }

    /**
     * Update default value
     *
     * @param unknown $defaultvalues default values
     * @param unknown $user          user
     * @param unknown $propname      prop name
     * @param unknown $fallback      fallback
     *
     * @return void
     */
    private function updatedefaultvalue(&$defaultvalues, $user, $propname, $fallback): void {
        $localkey = "user" . $propname;
        if (!array_key_exists($localkey, $defaultvalues) || $defaultvalues[$localkey] == "") {
            // We have an empty value, so change it.
            if ($user->$propname != "") {
                $defaultvalues[$localkey] = $user->$propname;
            } else {
                $defaultvalues[$localkey] = $fallback;
            }
        }
    }

    /**
     * Data preprocessing
     *
     * @param unknown $defaultvalues default values
     *
     * @return void
     */
    public function data_preprocessing(&$defaultvalues): void {
        global $DB;
        global $USER;
        // Prime the default values using the database entries that we've stored away.
        $user = $DB->get_record('user', ['id' => $USER->id]);
        $config = get_config('wims');
        $this->updatedefaultvalue($defaultvalues, $user, "firstname", "anonymous");
        $this->updatedefaultvalue($defaultvalues, $user, "lastname", "supervisor");
        $this->updatedefaultvalue($defaultvalues, $user, "email", "noreply@wimsedu.info");
        $this->updatedefaultvalue($defaultvalues, $user, "institution", $config->defaultinstitution);

        // Try to contact the WIMS server and see if the course already exists.
        if (is_object($this->cm)) {
            /* include_once(dirname(__FILE__) . '/wimsinterface.class.php'); */
            $wims = new wims_interface($config, debug:$config->debugsettings);
            $configfromwims = $wims->getclassconfigformodule($this->cm);
            $this->configfromwims = $configfromwims;
            // If the server sent us a config record then apply it.
            if ($configfromwims) {
                // Check for a class name.
                if (array_key_exists("description", $configfromwims)) {
                    $defaultvalues["name"] = $configfromwims["description"];
                }
                // Process the rest of the parameters.
                foreach ($configfromwims as $key => $val) {
                    $localkey = "user" . $key;
                    if (array_key_exists($localkey, $defaultvalues)) {
                        $defaultvalues[$localkey] = $val;
                    }
                }
            }
        }
    }

    /**
     * Validation
     *
     * @param unknown $data  data
     * @param unknown $files files
     *
     * @return array The list of errors keyed by element name (given by moodleform_mod parent validation)
     */
    public function validation($data, $files) {
        // If the course module has been instantiated already then put in an update request to WIMS.
        if (is_object($this->cm)) {
            // Extract the properties that are of relevance for WIMS and organise them into a candidate data array.
            $wimsdata = [
                "description" => $data["name"],
                "institution" => $data["userinstitution"],
                "supervisor" => $data["userfirstname"] . " " . $data["userlastname"],
                "email" => $data["useremail"],
                "lastname" => $data["userlastname"],
                "firstname" => $data["userfirstname"],
            ];

            // Copy out any data values that have changed into to the 'changed data' array.
            $changeddata = [];
            foreach ($wimsdata as $key => $val) {
                if ($this->configfromwims[$key] !== $val) {
                    $changeddata[$key] = $val;
                }
            }

            // Iterate over worksheets and exams.
            if (property_exists($this, 'configfromwims') === true) {
                foreach (["worksheets", "exams"] as $sheettype) {
                    $changeddata[$sheettype] = [];
                    foreach ($this->configfromwims[$sheettype] as $sheetidx => $sheetprops) {
                        // Fetch parameters from data.
                        $gradedkey = 'sheetgraded' . $sheettype . $sheetidx;
                        $title = $data['sheettitle' . $sheettype . $sheetidx];
                        $graded = array_key_exists($gradedkey, $data) ? $data[$gradedkey] : '';
                        $expiry = $data['sheetexpiry' . $sheettype . $sheetidx];
                        // Compose full title.
                        $gradestr = ($graded === '1') ? ' *' : '';
                        $fulltitle = trim($title) . $gradestr;
                        // Compose the expiry date.
                        $dateobj = new DateTime('@' . $expiry, new DateTimeZone('UTC'));
                        $expirydate = $dateobj->format('Ymd');

                        // Determine whether anything has changed.
                        if ($sheetprops['title'] !== $fulltitle || $sheetprops['expiration'] !== $expirydate) {
                            // Write the properties to the output data structure.
                            $changeddata[$sheettype][$sheetidx] = [];
                            $changeddata[$sheettype][$sheetidx]['title'] = $fulltitle;
                            $changeddata[$sheettype][$sheetidx]['expiration'] = $expirydate;
                        }
                    }
                }
            }

            // Put a call in to the wims server to update parameters.
            /* include_once(dirname(__FILE__) . '/wimsinterface.class.php'); */
            $config = get_config('wims');
            $wims = new wims_interface($config, debug:$config->debugsettings);
            $wims->updateclassconfigformodule($this->cm, $changeddata);
        }
        // Delegate to parent class.
        $data['instance'] = (int)$data['instance'];
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
