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
 * WIMS module version information
 *
 * @package   mod_wims
 * @copyright 2015 Edunao SAS <contact@edunao.com> / 2020 UCA
 * @author    Sadge <daniel@edunao.com> / Badatos <bado@unice.fr>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->component = 'mod_wims';     // Full name of the plugin (used for diagnostics).
$plugin->release = '0.5.3';          // Don't forget to update the version too.
$plugin->version = 2023042000;       // The current module version (Date: YYYYMMDDXX).
$plugin->requires = 2020061500;      // Requires this Moodle version (3.9).

// Moodle versions that are outside of this range will produce a message notifying at install time, but will allow for installation.
$plugin->supported = [39, 402];      // Moodle 3.9.x to 4.2.x are supported.

$plugin->maturity = MATURITY_STABLE; // Must be one of MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC or MATURITY_STABLE.
