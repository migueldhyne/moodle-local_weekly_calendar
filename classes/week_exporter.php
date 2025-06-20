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
 * Contains class for displaying the week view.
 *
 * @package     local_weekly_calendar
 * @copyright   2024 Patrick ROCHET <prochet.94@free.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_weekly_calendar;

use core\external\exporter;
use renderer_base;
use moodle_url;

/**
 * Class for displaying the week view.
 *
 * @package     local_weekly_calendar
 * @copyright   2024 Patrick ROCHET <prochet.94@free.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class week_exporter extends exporter {

    /** @var int Number of calendar instances displayed. */
    protected static $calendarinstances = 0;

    /** @var int This calendar instance's ID. */
    protected $calendarinstanceid = 0;

    /**
     * @var \calendar_information $calendar The calendar to be rendered.
     */
    protected $calendar;

    /**
     * @var int $firstdayofweek The first day of the week.
     */
    protected $firstdayofweek;

    /**
     * @var moodle_url $url The URL for the events page.
     */
    protected $url;

    /**
     * @var bool $includenavigation Whether navigation should be included on the output.
     */
    protected $includenavigation = true;

    /**
     * @var bool $initialeventsloaded Whether the events have been loaded for this month.
     */
    protected $initialeventsloaded = true;

    /**
     * @var bool $showcoursefilter Whether to render the course filter selector as well.
     */
    protected $showcoursefilter = false;

    /**
     * Constructor for month_exporter.
     *
     * @param \calendar_information $calendar The calendar being represented
     * @param \core_calendar\type_base $type The calendar type (e.g. Gregorian)
     * @param array $related The related information
     */
    public function __construct(\calendar_information $calendar, \core_calendar\type_base $type, $related) {
        // Increment the calendar instances count on initialisation.
        self::$calendarinstances++;
        // Assign this instance an ID based on the latest calendar instances count.
        $this->calendarinstanceid = self::$calendarinstances;
        $this->calendar = $calendar;
        $this->firstdayofweek = $type->get_starting_weekday();

        $this->url = new moodle_url('/local/weekly_calendar/view.php', [
                'view' => 'week',
                'time' => $calendar->time,
            ]);

        if ($this->calendar->course && SITEID !== $this->calendar->course->id) {
            $this->url->param('course', $this->calendar->course->id);
        } else if ($this->calendar->categoryid) {
            $this->url->param('category', $this->calendar->categoryid);
        }

        $related['type'] = $type;

        $data = [
            'url' => $this->url->out(false),
        ];

        parent::__construct($data, $related);
    }

    /**
     * Return the list of properties.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'url' => [
                'type' => PARAM_URL,
            ],
        ];
    }

    /**
     * Return the list of additional properties.
     *
     * @return array
     */
    protected static function define_other_properties() {
        return [
            'courseid' => [
                'type' => PARAM_INT,
            ],
            'categoryid' => [
                'type' => PARAM_INT,
                'optional' => true,
                'default' => 0,
            ],
            'filter_selector' => [
                'type' => PARAM_RAW,
                'optional' => true,
            ],
            'week' => [
                'type' => PARAM_RAW,
                'multiple' => true,
            ],
            'daynames' => [
                'type' => \core_calendar\external\day_name_exporter::read_properties_definition(),
                'multiple' => true,
            ],
            'view' => [
                'type' => PARAM_ALPHA,
            ],
            'date' => [
                'type' => \core_calendar\external\date_exporter::read_properties_definition(),
            ],
            'periodname' => [
                // Note: We must use RAW here because the calendar type returns the formatted month name based on a
                // calendar format.
                'type' => PARAM_RAW,
            ],
            'includenavigation' => [
                'type' => PARAM_BOOL,
                'default' => true,
            ],
            // Tracks whether the first set of events have been loaded and provided
            // to the exporter.
            'initialeventsloaded' => [
                'type' => PARAM_BOOL,
                'default' => true,
            ],
            'previousperiod' => [
                'type' => \core_calendar\external\date_exporter::read_properties_definition(),
            ],
            'previousperiodlink' => [
                'type' => PARAM_URL,
            ],
            'previousperiodname' => [
                // Note: We must use RAW here because the calendar type returns the formatted month name based on a
                // calendar format.
                'type' => PARAM_RAW,
            ],
            'nextperiod' => [
                'type' => \core_calendar\external\date_exporter::read_properties_definition(),
            ],
            'nextperiodname' => [
                // Note: We must use RAW here because the calendar type returns the formatted month name based on a
                // calendar format.
                'type' => PARAM_RAW,
            ],
            'nextperiodlink' => [
                'type' => PARAM_URL,
            ],
            'larrow' => [
                // The left arrow defined by the theme.
                'type' => PARAM_RAW,
            ],
            'rarrow' => [
                // The right arrow defined by the theme.
                'type' => PARAM_RAW,
            ],
            'defaulteventcontext' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'calendarinstanceid' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'viewingmonth' => [
                'type' => PARAM_BOOL,
                'default' => true,
            ],
            'showviewselector' => [
                'type' => PARAM_BOOL,
                'default' => true,
            ],
        ];
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer.
     * @return array Keys are the property names, values are their values.
     */
    protected function get_other_values(renderer_base $output) {
        $currentperiod = $this->get_week_data("current");
        $previousperiod = $this->get_week_data("previous");
        $nextperiod = $this->get_week_data("next");
        $date = $this->related['type']->timestamp_to_date_array($this->calendar->time);

        $nextperiodlink = new moodle_url($this->url);
        $nextperiodlink->param('time', $nextperiod[0]);

        $previousperiodlink = new moodle_url($this->url);
        $previousperiodlink->param('time', $previousperiod[0]);

        $viewmode = $this->calendar->get_viewmode() ?? 'month';

        $return = [
            'courseid' => $this->calendar->courseid,
            'week' => $this->get_week($output),
            'daynames' => $this->get_day_names($output),
            'view' => $viewmode,
            'date' => (new \core_calendar\external\date_exporter($date))->export($output),

            'periodname' => $this->get_period_name($currentperiod[0]),
            'previousperiod' => (new \core_calendar\external\date_exporter($previousperiod))->export($output),
            'previousperiodname' => $this->get_period_name($previousperiod[0]),
            'previousperiodlink' => $previousperiodlink->out(false),
            'nextperiod' => (new \core_calendar\external\date_exporter($nextperiod))->export($output),
            'nextperiodname' => $this->get_period_name($nextperiod[0]),
            'nextperiodlink' => $nextperiodlink->out(false),

            'larrow' => $output->larrow(),
            'rarrow' => $output->rarrow(),
            'includenavigation' => $this->includenavigation,
            'initialeventsloaded' => $this->initialeventsloaded,
            'calendarinstanceid' => $this->calendarinstanceid,
            'showviewselector' => $viewmode === 'week',
        ];

        if ($this->showcoursefilter) {
            $return['filter_selector'] = $this->get_course_filter_selector($output);
        }

        if ($context = $this->get_default_add_context()) {
            $return['defaulteventcontext'] = $context->id;
        }

        if ($this->calendar->categoryid) {
            $return['categoryid'] = $this->calendar->categoryid;
        }

        return $return;
    }

    /**
     * Get the course filter selector.
     *
     * @param renderer_base $output
     * @return string The html code for the course filter selector.
     */
    protected function get_course_filter_selector(renderer_base $output) {
        $content = '';
        $content .= $output->course_filter_selector($this->url, '', $this->calendar->course->id, $this->calendarinstanceid);

        return $content;
    }

    /**
     * Get the list of day names for display, re-ordered from the first day
     * of the week.
     *
     * @param   renderer_base $output
     * @return  day_name_exporter[]
     */
    protected function get_day_names(renderer_base $output) {
        $weekdays = $this->related['type']->get_weekdays();
        $daysinweek = count($weekdays);

        $daynames = [];
        for ($i = 0; $i < $daysinweek; $i++) {
            // Bump the currentdayno and ensure it loops.
            $dayno = ($i + $this->firstdayofweek + $daysinweek) % $daysinweek;
            $dayname = new \core_calendar\external\day_name_exporter($dayno, $weekdays[$dayno]);
            $daynames[] = $dayname->export($output);
        }

        return $daynames;
    }

    /**
     * Get week.
     *
     * @param   renderer_base $output
     * @return  array
     */
    protected function get_week(renderer_base $output) {
        global $CFG;

        $return = new \stdClass;
        $return->prepadding = [];
        $return->postpadding = [];
        $return->days = [];

        $today = $this->related['type']->timestamp_to_date_array(time());

        $weekend = CALENDAR_DEFAULT_WEEKEND;
        if (isset($CFG->calendar_weekend)) {
            $weekend = intval($CFG->calendar_weekend);
        }
        $numberofdaysinweek = $this->related['type']->get_num_weekdays();

        $date = $this->related['type']->timestamp_to_date_array($this->calendar->time);
        $timestamp = $this->related['type']->convert_to_timestamp($date['year'], $date['mon'], $date['mday']);
        // Firstday must be the first day of the current week.
        $currentwday = $date['wday'];
        $firstday = $date['mday'] - $currentwday + 1;
        $days = [];
        for ($dayno = $firstday; $dayno < $firstday + 7; $dayno++) {
            // Get the gregorian representation of the day.
            $timestamp = $this->related['type']->convert_to_timestamp($date['year'], $date['mon'], $dayno);
            $days[] = $this->related['type']->timestamp_to_date_array($timestamp);
        }

        foreach ($days as $daydata) {
            $events = [];
            foreach ($this->related['events'] as $event) {
                $times = $event->get_times();
                $starttime = $times->get_start_time()->getTimestamp();
                $startdate = $this->related['type']->timestamp_to_date_array($starttime);
                $endtime = $times->get_end_time()->getTimestamp();
                $enddate = $this->related['type']->timestamp_to_date_array($endtime);

                if ((($startdate['year'] * 366) + $startdate['yday']) > ($daydata['year'] * 366) + $daydata['yday']) {
                    // Starts after today.
                    continue;
                }
                if ((($enddate['year'] * 366) + $enddate['yday']) < ($daydata['year'] * 366) + $daydata['yday']) {
                    // Ends before today.
                    continue;
                }
                $events[] = $event;
            }

            $istoday = true;
            $istoday = $istoday && $today['year'] == $daydata['year'];
            $istoday = $istoday && $today['yday'] == $daydata['yday'];
            $daydata['istoday'] = $istoday;

            $daydata['isweekend'] = !!($weekend & (1 << ($daydata['wday'] % $numberofdaysinweek)));

            $day = new \local_weekly_calendar\external\week_day_exporter($this->calendar, $daydata, [
                'events' => $events,
                'cache' => $this->related['cache'],
                'type' => $this->related['type'],
            ]);

            $return->days[] = $day->export($output);
        }

        return $return;
    }

    /**
     * Returns a list of objects that are related.
     *
     * @return array
     */
    protected static function define_related() {
        return [
            'events' => '\core_calendar\local\event\entities\event_interface[]',
            'cache' => '\core_calendar\external\events_related_objects_cache',
            'type' => '\core_calendar\type_base',
        ];
    }
    /**
     * Fetches all the events for a given week.
     *
     * @param string $when Which week to fetch ("current", "previous" or "next").
     * @return array An array of day-by-day event data.
     */
    protected function get_week_data($when) {
        $type = $this->related['type'];
        $currenttime = $this->calendar->time;
        $onedaytime = 60 * 60 * 24;
        $oneweektime = 7 * $onedaytime;
        $date = $type->timestamp_to_date_array($this->calendar->time);
        $wday = $date['wday'];
        switch ($when) {
            case 'current':
                $time = $currenttime - ($wday - 1) * $onedaytime;
                break;
            case 'next':
                $time = $currenttime - ($wday - 1) * $onedaytime + $oneweektime;
                break;
            case 'previous':
                $time = $currenttime - ($wday - 1) * $onedaytime - $oneweektime;
            break;
        }
        $data = $type->timestamp_to_date_array($time);

        return $data;
    }

    /**
     * Builds a human-readable name for a date period.
     *
     * @param int $starttime Timestamp for period start.
     * @return string The formatted period name (e.g. "Mar 1 – Mar 7, 2025").
     */
    protected function get_period_name($starttime) {
        $periodname = "";
        $onedaytime = 60 * 60 * 24;
        $endtime = $starttime + 6 * $onedaytime;
        $daystarttime = userdate($starttime, '%d');
        $dayendtime = userdate($endtime, '%d');
        $monthstarttime = userdate($starttime, '%B');
        $monthendtime = userdate($endtime, '%B');
        $yearstarttime = userdate($starttime, '%Y');
        $yearendtime = userdate($endtime, '%Y');
        if ($monthstarttime == $monthendtime) {
            $periodname = "$daystarttime - $dayendtime $monthstarttime $yearstarttime";
        } else {
            if ($yearstarttime == $yearendtime) {
                $periodname = "$daystarttime $monthstarttime - $dayendtime $monthendtime $yearstarttime";
            } else {
                $periodname = "$daystarttime $monthstarttime $yearstarttime - $dayendtime $monthendtime $yearendtime";
            }
        }
        return $periodname;
    }

    /**
     * Set whether the navigation should be shown.
     *
     * @param   bool    $include
     * @return  $this
     */
    public function set_includenavigation($include) {
        $this->includenavigation = $include;
        return $this;
    }

    /**
     * Set whether the initial events have already been loaded and
     * provided to the exporter.
     *
     * @param   bool    $loaded
     * @return  $this
     */
    public function set_initialeventsloaded(bool $loaded) {
        $this->initialeventsloaded = $loaded;

        return $this;
    }

    /**
     * Set whether the course filter selector should be shown.
     *
     * @param   bool    $show
     * @return  $this
     */
    public function set_showcoursefilter(bool $show) {
        $this->showcoursefilter = $show;

        return $this;
    }

    /**
     * Get the default context for use when adding a new event.
     *
     * @return null|\context
     */
    protected function get_default_add_context() {
        if (calendar_user_can_add_event($this->calendar->course)) {
            return \context_course::instance($this->calendar->course->id);
        }

        return null;
    }
}
