<?php
/**
 * This file is part of sPof.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @copyright 2003-2014 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
 * @lincense  http://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link      http://spof.fem-net.de
 */

namespace FeM\sPof;

/**
 * This class represents an object model for event collection.
 *
 * @method int nextDay() nextDay()
 * @method int thisDay() thisDay()
 * @method int previousDay() previousDay()
 *
 * @method int nextMonth() nextMonth()
 * @method int thisMonth() thisMonth()
 * @method int previousMonth() previousMonth()
 *
 * @method int nextYear() nextYear()
 * @method int thisYear() thisYear()
 * @method int previousYear() previousYear()
 *
 * @package FeM\sPof
 * @author mybr
 * @author pegro
 * @since 1.0
 */
class Calendar
{
    /**
     * Contains german month names for conversion from a month's number to its corresponding name.
     *
     * @var array $MONTHS
     */
    private static $MONTHS = [
        'Januar',
        'Februar',
        'März',
        'April',
        'Mai',
        'Juni',
        'Juli',
        'August',
        'September',
        'Oktober',
        'November',
        'Dezember'
    ];

    /**
     * Calendar start
     *
     * @internal
     *
     * @var \DateTime
     */
    private $start;

    /**
     * Calendar end
     *
     * @internal
     *
     * @var \DateTime
     */
    private $end;

    /**
     * Contains the event instances
     *
     * @internal
     *
     * @var int
     */
    private $eventcount = 0;

    /**
     * List of events.
     *
     * @internal
     *
     * @var array
     */
    private $events = [];

    /**
     * Contains references to events structured by date
     *
     * @internal
     *
     * @var array
     */
    private $calendar = [];


    /**
     * The object constructor. It is used to assign a specific timespan to the calendar.
     *
     * @api
     *
     * @param \DateTime $startDate The beginning of the timespan of the calendar.
     * @param \DateTime $endDate   The end of the timespan of the calendar.
     */
    public function __construct($startDate, $endDate)
    {
        // assign start Date
        if (is_string($startDate)) {
            $this->start = new \DateTime($startDate);
        } elseif (strcmp(get_class($startDate), "DateTime") == 0) {
            $this->start = $startDate;
        } else {
            $this->start = new \DateTime();
        }
        // assign end Date
        if (is_string($endDate)) {
            $this->end = new \DateTime($endDate);
        } elseif (strcmp(get_class($endDate), "DateTime") == 0) {
            $this->end = $endDate;
        } else {
            $this->end = new \DateTime();
        }
    } // constructor


    /**
     * Returns the timestamp of the beginning of the timespan contained by the calendar.
     *
     * @api
     *
     * @return int The timestamp.
     */
    public function getStart()
    {
        return $this->start->getTimestamp();
    } // function


    /**
     * Returns the timestamp of the ending of the timespan contained by the calendar.
     *
     * @api
     *
     * @return int The timestamp.
     */
    public function getEnd()
    {
        return $this->end->getTimestamp();
    } // function


    /**
     * Adds an event to the calendar. If the event does not happen in the timespan contained by the calendar, it will
     * be dropped.
     *
     * @api
     *
     * @param array $event  The event to be inserted, with 'start' and 'end' indexes containing the dates.
     */
    public function addEvent(array $event)
    {
        // Drop event if it doesn't take place in the timespan of the calendar
        if ($event['start'] > $this->getEnd() ||
            $event['end'] < $this->getStart()) {
            return;
        }

        // Else store it in the event array
        $this->events[$this->eventcount] = $event;

        // Start with the first occurence of the event
        // in the timespan handled by this calendar instance
        if ($event['start'] < $this->getStart()) {
            $startDate = $this->start;
        } else {
            $startDate = new \DateTime();
            $startDate->setTimestamp($event['start']);
            // cut off hours, minutes and seconds
            $startDate = new \DateTime($startDate->format("Y-m-j"));
        }

        if ($event['end'] > $this->getEnd()) {
            $endDate = $this->end;
        } else {
            $endDate = new \DateTime();
            $endDate->setTimestamp($event['end']);
            // cut off hours, minutes and seconds
            $endDate = new \DateTime($endDate->format("Y-m-j"));
        }

        // get the offset of the first occurence to
        // the start of the calendar
        $offset = (int)$this->start->diff($startDate)->format('%a');

        // get the offset from the first occurence to the last occurence
        $interval = (int)$startDate->diff($endDate)->format('%a');

        // Now iterate through all days that are overlapped by
        // the timespan of this event
        for ($i = $offset; $i <= $offset + $interval; ++$i) {
            $this->calendar[$i][] = $this->events[$this->eventcount];
        }
        $this->eventcount++;
    } // function


    /**
     * Returns an array that contains all collected events in the given timespan. The result depends on the timespan
     * assigned to the calendar.
     *
     * @internal
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @return array An array that contains all collected events in the given timespan.
     */
    private function fetch($start, $end)
    {
        if (get_class($start) === "DateTime" && get_class($end) === "DateTime") {
            $offset = (int)$this->start->diff($start)->format('%a');
            $length = (int)$start->diff($end)->format('%a');
            $result = [];
            for ($i = $offset; $i < $offset + $length; ++$i) {
                if (!isset($this->calendar[$i])) {
                    $result[] = [];
                } else {
                    $result[] = $this->calendar[$i];
                }
            }
            return $result;
        } else {
            return [];
        }
    } // function


    /**
     * Returns an array that contains all collected events for the given year. The result depends on the timespan
     * assigned to the calendar.
     *
     * @api
     *
     * @param int $year
     *
     * @return array An array that contains all collected events for the given year.
     */
    public function fetchYear($year)
    {
        return $this->fetch(new \DateTime($year."-1-1"), new \DateTime(($year + 1)."-1-1"));
    } // function


    /**
     * Returns an array that contains all collected events for the given month. The result depends on the timespan
     * assigned to the calendar.
     *
     * @api
     *
     * @param int $year
     * @param int $month
     *
     * @return array An array that contains all collected events for the given month.
     */
    public function fetchMonth($year, $month)
    {
        $startDate = new \DateTime($year."-".$month."-1");
        $endDate = new \DateTime();
        $endDate->setTimestamp($startDate->getTimeStamp());
        $endDate->modify("+1 month");

        return $this->fetch($startDate, $endDate);
    } // function


    /**
     * Returns an array that contains all collected events for the given calendar week. The result depends on the
     * timespan assigned to the calendar.
     *
     * @api
     *
     * @param int     $year
     * @param int     $week
     *
     * @return array An array that contains all collected events for the given week.
     */
    public function fetchWeek($year, $week)
    {
        if ($week < 10) {
            $week = "0".$week;
        }
        $startDate = new \DateTime($year."-W".$week);
        $endDate = new \DateTime();
        $endDate->setTimestamp($startDate->getTimeStamp());
        $endDate->modify("+1 week");

        return $this->fetch($startDate, $endDate);
    } // function


    /**
     * Returns an array that contains all collected events for the given day. The result depends on the timespan
     * assigned to the calendar.
     *
     * @api
     *
     * @param int $year
     * @param int $month
     * @param int $day
     *
     * @return array An array that contains all collected events for the given day.
     */
    public function fetchDay($year, $month, $day)
    {
        $startDate = new \DateTime($year."-".$month."-".$day);
        $endDate = new \DateTime();
        $endDate->setTimestamp($startDate->getTimeStamp());
        $endDate->modify("+1 day");
        $result = $this->fetch($startDate, $endDate);

        return $result[0];
    } // function


    /**
     * Returns the numerical representation of the first week day in the given month and year.
     *
     * @api
     *
     * @param int $month The month to be assumed.
     * @param int $year  The year to be assumed.
     *
     * @return int The numerical representation of the first week day.
     */
    public function getDayOffset($month, $year)
    {
        $date = new \DateTime($year."-".$month."-1");

        return (int)$date->format('N');
    } // function


    /**
     * Returns the number of days in the given month.
     *
     * @api
     *
     * @param int $month The month to be assumed.
     *
     * @return int The number of days in the given month.
     */
    public function getDaysOfMonth($month)
    {
        $date = new \DateTime($this->thisYear()."-".$month."-1");

        return (int)$date->format('t');
    } // function


    /**
     * Returns the number of the calendar week matching the given day in the given month.
     *
     * @api
     *
     * @param int $day The day to be assumed.
     * @param int $month The month to be assumed.
     *
     * @return int The number of the calendar week.
     */
    public function getWeekNumber($day, $month)
    {
        $date = new \DateTime($this->thisYear()."-".$month."-".$day);
        return (int)$date->format('W');
    } // function


    /**
     * Converts the numerical representation of the given month to the matching german word.
     *
     * @api
     *
     * @param int $month The number to be converted.
     *
     * @return string The matching german word.
     */
    public function monthToString($month)
    {
        return self::$MONTHS[$month - 1];
    } // function

    /**
     * if first param is given and true, return complete timestamp instead of a single value
     *
     * @internal
     *
     * @throws exception\NotImplementedException
     *
     * @param string $name
     * @param array $argv
     *
     * @return string
     */
    public function __call($name, array $argv)
    {
        // define method name put the table name on first parameter
        if (strpos($name, 'this') === 0) {
            $name = str_replace('this', '', $name);
        } elseif (strpos($name, 'next') === 0) {
            $name = str_replace('next', '', $name);
            $modify = '+1';
        } elseif (strpos($name, 'previous') === 0) {
            $name = str_replace('previous', '', $name);
            $modify = '-1';
        } else {
            throw new exception\NotImplementedException(_s('Calendar: Unbekannte Methode: ').$name);
        }

        // name to format transition
        $toFormat = [
            'Day' => 'j',
            'Month' => 'n',
            'Year' => 'Y'
        ];


        // get selected date value format (day, month or year)
        if (!isset($toFormat[$name])) {
            throw new exception\NotImplementedException(_s('Calendar: Unbekannte Methode: ').$name);
        }
        $format = $toFormat[$name];

        // get start time and modify it by method name
        $nextDate = new \DateTime($this->start->format("Y-m-d"));
        if (isset($modify)) {
            $nextDate->modify($modify.' '.$name);
        }

        // if first param is true, return complete timestamp instead of single value
        if (count($argv) > 0 && $argv[0] === true) {
            $format = '';
        }

        if (strlen($format) === 1) {
            return (int)$nextDate->format($format);
        }

        return $nextDate->getTimeStamp();
    } // magic call
}// class
