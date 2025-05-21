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
 * Defines the version details.
 *
 * @package     local_advanced_calendar
 * @copyright   2024 Patrick ROCHET <prochet.94@free.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component      = 'local_advanced_calendar';
$plugin->version        = 2024120500;
$plugin->release        = '4.0.2';
$plugin->requires       = 2022041902; // 4.0.2
$plugin->maturity       = MATURITY_STABLE;
$plugin->dependencies   = ['local_dompdf' => 2021062802];
