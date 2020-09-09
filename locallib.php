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
 * Library of functions for WIMS outside of the core api
 *
 * @package   mod_wims
 * @copyright 2020 UCA <univ-cotedazur.fr>
 * @author    Badatos <bado@unice.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/wims/lib.php');

// Define Event types.
define('WIMS_EVENT_TYPE_DUE', 'due');

/**
 * Update the calendar entries for the current wims activity.
 * See @link{https://docs.moodle.org/dev/Calendar_API}
 *
 * @param stdClass $data The row from the database table wims.
 * @param int      $cmid The coursemodule id
 * @return bool
 */
function wims_update_calendar($data, $cmid) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/calendar/lib.php');

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'wims', $data->id, $completiontimeexpected);

    $event = new \stdClass();

    if (!empty($data->duedate)) {
        $event->eventtype = WIMS_EVENT_TYPE_DUE; // Can be any string value you want.
        $event->type = CALENDAR_EVENT_TYPE_ACTION; // Action events are displayed on the block_myoverview.
        // $event->type = CALENDAR_EVENT_TYPE_STANDARD; // Events not needed on the block_myoverview.
        $event->name = get_string('calendardue', 'wims', $data->name);
        $event->description = format_module_intro('wims', $data, $cmid, false);
        $event->format = FORMAT_HTML;
        $event->courseid = $data->course;
        $event->groupid = 0;
        $event->userid = 0;
        $event->modulename = 'wims';
        $event->instance = $data->id;
        $event->timestart = $data->duedate;
        $event->timesort = $data->duedate;
        $event->timeduration = 0;
        $event->visible = instance_is_visible('wims', $data);
        $event->priority = null; // NULL for non-override events.
    }

    $event->id = $DB->get_field('event', 'id',
            array('modulename' => 'wims', 'instance' => $data->id, 'eventtype' => WIMS_EVENT_TYPE_DUE));

    // Calendar event exists so update it.
    if ($event->id) {
        $calendarevent = calendar_event::load($event->id);
        if (!empty($data->duedate)) {
            $calendarevent->update($event);
        } else {
            // Calendar event is no longer needed.
            $calendarevent->delete();
        }
    } else if (!empty($data->duedate)) {
        // Event doesn't exist so create one.
        calendar_event::create($event);
    }

    return true;
}
