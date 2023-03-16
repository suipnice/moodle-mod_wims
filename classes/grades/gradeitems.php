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
 * Grade item mappings for the activity.
 *
 * @package   mod_wims
 * @copyright 2020 UCA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types = 1);

namespace mod_wims\grades;

use \core_grades\local\gradeitem\itemnumber_mapping;

/**
 * Grade item mappings for the activity.
 *
 * Since Moodle 3.8, activities which define multiple grade items must describe the mapping of
 *  the gradeitem's itemnumber to a meaningful name.
 *
 * @package   mod_wims
 * @copyright 2020 UCA
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradeitems implements itemnumber_mapping {

    /**
     * Return the list of grade item mappings for the activity.
     *
     * @return array
     */
    public static function get_itemname_mapping_for_component(): array {
        // For legacy compatibility, when the itemnumber is 0,
        // the itemname will always be empty (see core_grades\component_gradeitems).
        $mapping = [0 => ''];

        for ($i = 1; $i < 64; $i++) {
            $mapping[$i] = "exam_$i";
        }
        return $mapping;
    }

}
