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
 * local_weekly_calendar plugin
 *
 * @package     local_weekly_calendar
 * @copyright   2024 Patrick ROCHET <prochet.94@free.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Get the calendar view output.
 *
 * @param   \calendar_information $calendar The calendar being represented
 * @param   string  $view The type of calendar to have displayed
 * @param   bool    $includenavigation Whether to include navigation
 * @param   bool    $skipevents Whether to load the events or not
 * @param   int     $lookahead Overwrites site and users's lookahead setting.
 * @return  array[array, string]
 */
function local_weekly_calendar_get_view(\calendar_information $calendar,
                                            $view,
                                            $includenavigation = true,
                                            bool $skipevents = false,
                                            ?int $lookahead = null) {
    global $PAGE, $CFG;

    $renderer = $PAGE->get_renderer('core_calendar');
    $type = \core_calendar\type_factory::get_calendar_instance();

    // Calculate the bounds of the month.
    $calendardate = $type->timestamp_to_date_array($calendar->time);

    $date = new \DateTime('now', core_date::get_user_timezone_object(99));
    $eventlimit = 0;

    if ($view === 'day') {
        $tstart = $type->convert_to_timestamp($calendardate['year'], $calendardate['mon'], $calendardate['mday']);
        $date->setTimestamp($tstart);
        $date->modify('+1 day');
    } else if ($view === 'week') {
        $tstart = $type->convert_to_timestamp($calendardate['year'], $calendardate['mon'], $calendardate['mday']);
        $weekday = $type->get_weekday($calendardate['year'], $calendardate['mon'], $calendardate['mday']);
        $timeoffset = ($weekday - 1) * 60 * 60 * 24;
        $tstart -= $timeoffset;
        $weekdays = 7;
        $date->setTimestamp($tstart);
        $date->modify('+' . $weekdays . ' day');

        $template = 'local_weekly_calendar/calendar_week';
    } else if ($view === 'upcoming' || $view === 'upcoming_mini') {
        // Number of days in the future that will be used to fetch events.
        if (!$lookahead) {
            if (isset($CFG->calendar_lookahead)) {
                $defaultlookahead = intval($CFG->calendar_lookahead);
            } else {
                $defaultlookahead = CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD;
            }
            $lookahead = get_user_preferences('calendar_lookahead', $defaultlookahead);
        }

        // Maximum number of events to be displayed on upcoming view.
        $defaultmaxevents = CALENDAR_DEFAULT_UPCOMING_MAXEVENTS;
        if (isset($CFG->calendar_maxevents)) {
            $defaultmaxevents = intval($CFG->calendar_maxevents);
        }
        $eventlimit = get_user_preferences('calendar_maxevents', $defaultmaxevents);

        $tstart = $type->convert_to_timestamp($calendardate['year'], $calendardate['mon'], $calendardate['mday'],
                $calendardate['hours']);
        $date->setTimestamp($tstart);
        $date->modify('+' . $lookahead . ' days');
    } else {
        $tstart = $type->convert_to_timestamp($calendardate['year'], $calendardate['mon'], 1);
        $monthdays = $type->get_num_days_in_month($calendardate['year'], $calendardate['mon']);
        $date->setTimestamp($tstart);
        $date->modify('+' . $monthdays . ' days');
        if ($view === 'mini' || $view === 'minithree') {
            $template = 'core_calendar/calendar_mini';
        } else {
            $template = 'core_calendar/calendar_month';
        }
    }

    // We need to extract 1 second to ensure that we don't get into the next day.
    $date->modify('-1 second');
    $tend = $date->getTimestamp();

    list($userparam, $groupparam, $courseparam, $categoryparam) = array_map(function($param) {
        // If parameter is true, return null.
        if ($param === true) {
            return null;
        }

        // If parameter is false, return an empty array.
        if ($param === false) {
            return [];
        }

        // If the parameter is a scalar value, enclose it in an array.
        if (!is_array($param)) {
            return [$param];
        }

        // No normalisation required.
        return $param;
    }, [$calendar->users, $calendar->groups, $calendar->courses, $calendar->categories]);

    if ($skipevents) {
        $events = [];
    } else {
        $events = \core_calendar\local\api::get_events(
            $tstart,
            $tend,
            null,
            null,
            null,
            null,
            $eventlimit,
            null,
            $userparam,
            $groupparam,
            $courseparam,
            $categoryparam,
            true,
            true,
            function ($event) {
                if ($proxy = $event->get_course_module()) {
                    $cminfo = $proxy->get_proxied_instance();
                    return $cminfo->uservisible;
                }

                if ($proxy = $event->get_category()) {
                    $category = $proxy->get_proxied_instance();

                    return $category->is_uservisible();
                }

                return true;
            }
        );
    }

    $related = [
        'events' => $events,
        'cache' => new \core_calendar\external\events_related_objects_cache($events),
        'type' => $type,
    ];

    $data = [];
    $calendar->set_viewmode($view);
    if ($view == "month" || $view == "monthblock" || $view == "mini" || $view == "minithree" ) {
        $month = new \core_calendar\external\month_exporter($calendar, $type, $related);
        $month->set_includenavigation($includenavigation);
        $month->set_initialeventsloaded(!$skipevents);
        $month->set_showcoursefilter(($view == "month" || $view == "monthblock"));
        $data = $month->export($renderer);
    } else if ($view == "week") {
        $week = new local_weekly_calendar\week_exporter($calendar, $type, $related);
        $week->set_includenavigation($includenavigation);
        $week->set_initialeventsloaded(!$skipevents);
        $week->set_showcoursefilter(true);
        $data = $week->export($renderer);

    } else if ($view == "day") {
        $day = new \core_calendar\external\calendar_day_exporter($calendar, $related);
        $data = $day->export($renderer);
        $data->viewingday = true;
        $data->showviewselector = true;
        $template = 'core_calendar/calendar_day';
    } else if ($view == "upcoming" || $view == "upcoming_mini") {
        $upcoming = new \core_calendar\external\calendar_upcoming_exporter($calendar, $related);
        $data = $upcoming->export($renderer);

        if ($view == "upcoming") {
            $template = 'core_calendar/calendar_upcoming';
            $data->viewingupcoming = true;
            $data->showviewselector = true;
        } else if ($view == "upcoming_mini") {
            $template = 'core_calendar/calendar_upcoming_mini';
        }
    }

    return [$data, $template];
}

