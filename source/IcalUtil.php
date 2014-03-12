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
 * This Class helps to read iCalaendar format (e.g. *.ics files), parse it and give an array with its content.
 *
 * Original Code by Martin Thoma <info@martin-thoma.de>, licensed under "MIT License", code borrowed from:
 * https://github.com/johngrogg/ics-parser with additional fixes and adjustments from:
 * dangerground <danny.goette@fem.tu-ilmenau.de> fixes code with missing UNTIL, some wrong variable names, multiline
 * commands, like descriptiona and adjustments to Project code standard.
 *
 * @internal
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
abstract class IcalUtil
{
    /**
     * Returns an array of arrays with all events. Every event is an associative
     * array and each property is an element it.
     *
     * @api
     *
     * @return array
     *
     * @param string $filename The path to the iCal-file
     */
    public static function readEventsFromFile($filename)
    {
        return self::readEventsFromString(file_get_contents($filename));
    } // function


    /**
     * Returns an array where each property is an element it. You should only pass calendar data which has one event,
     * because everything else won't get returned.
     *
     * @internal
     *
     * @param string $calendarData the calendardata as string
     *
     * @return array|false
     */
    public static function readEventFromString($calendarData)
    {
        $events = self::readEventsFromString($calendarData);
        if (count($events) > 0) {
            return $events[0];
        }

        return false;
    } // function


    /**
     * Returns an array of arrays with all events. Every event is an associative
     * array and each property is an element it.
     *
     * @internal
     *
     * @throws \InvalidArgumentException
     *
     * @param string $calendarData the calendardata as string
     *
     * @return array
     */
    public static function readEventsFromString($calendarData)
    {
        $type = '';
        $event_count = 0;
        $cal = [];

        // load and fix multiline commands
        $content = preg_replace("#\n[[:space:]]+#s", '', $calendarData);

        $lines = explode("\n", $content);
        if (stristr($lines[0], 'BEGIN:VCALENDAR') === false) {
            throw new \InvalidArgumentException('File does not start with BEGIN:VCALENDAR or is no proper ICS file.');
        }

        foreach ($lines as $line) {
            $line = trim($line);

            preg_match("/([^:]+):([\w\W]*)/", $line, $matches);
            if (count($matches) == 0) {
                self::addCalendarComponentWithKeyAndValue($type, false, $line, $event_count, $cal);
                continue;
            }
            $add = ['keyword' => $matches[1], 'value' => $matches[2]];

            switch ($line) {
                // http://www.kanzaki.com/docs/ical/vtodo.html
                case "BEGIN:VTODO":
                    $type = "VTODO";
                    break;

                // http://www.kanzaki.com/docs/ical/vevent.html
                case "BEGIN:VEVENT":
                    $event_count++;
                    $type = "VEVENT";
                    break;

                //all other special strings
                case "BEGIN:VCALENDAR":
                case "BEGIN:DAYLIGHT":
                    // http://www.kanzaki.com/docs/ical/vtimezone.html
                case "BEGIN:VTIMEZONE":
                case "BEGIN:STANDARD":
                    $type = $add['value'];
                    break;
                case "END:VTODO": // end special text - goto VCALENDAR key
                case "END:VEVENT":
                case "END:VCALENDAR":
                case "END:DAYLIGHT":
                case "END:VTIMEZONE":
                case "END:STANDARD":
                    $type = "VCALENDAR";
                    break;
                default:
                    self::addCalendarComponentWithKeyAndValue(
                        $type,
                        $add['keyword'],
                        $add['value'],
                        $event_count,
                        $cal
                    );
                    break;
            }
        }
        return $cal['VEVENT'];
    } // function


    /**
     * Add to $cal array one value and key.
     *
     * @internal
     *
     * @param string $component This could be VTODO, VEVENT, VCALENDAR, ...
     * @param string $keyword The keyword, for example DTSTART
     * @param string $value The value, for example 20110105T090000Z
     * @param int $event_count
     * @param array $cal
     */
    protected static function addCalendarComponentWithKeyAndValue($component, $keyword, $value, $event_count, &$cal)
    {
        static $last_keyword;

        if (strstr($keyword, ';')) {

            $parts = explode(';', $keyword);
            foreach ($parts as $part) {
                if (strpos($part, 'TZID=') !== false) {
                    $timezone = preg_replace(
                        '#^(.*)TZID=(/freeassociation.sourceforge.net/Tzfile/)?([^;]+)(.*)$#',
                        '$3',
                        $part
                    );
                    $tz = new \DateTimeZone(preg_replace('#\s#', '', $timezone));
                    if ($tz !== false) {
                        $ct = new \DateTime('now', new \DateTimeZone('UTC'));
                        $value .= ' +'.str_pad($tz->getOffset($ct)/36, 4, '0', STR_PAD_LEFT);
                    }
                }
            }

            // Ignore everything in keyword after a ; (things like Language, etc)
            $keyword = substr($keyword, 0, strpos($keyword, ";"));
        }

        // unescaping
        $replace = [
            '\,' => ",",
            '\;' => ";",
            '\n' =>"\n",
        ];
        $value = str_replace(array_keys($replace), $replace, $value);

        if ($keyword == false) {
            $keyword = $last_keyword;
            switch ($component) {
                case 'VEVENT':
                    $value = $cal[$component][$event_count - 1][$keyword].$value;
                    break;
            }
        }
        
        if (stristr($keyword, "DTSTART") or stristr($keyword, "DTEND")) {
            $keyword = explode(";", $keyword);
            $keyword = $keyword[0];
        }

        switch ($component) {
            case "VEVENT":
                $cal[$component][$event_count - 1][$keyword] = $value;
                break;
            default:
                $cal[$component][$keyword] = $value;
                break;
        }
        $last_keyword = $keyword;
    } // function


    /**
     * Convert vCalendar Timestamp to php \DateTime object
     *
     * @api
     *
     * @param string $datestring
     *
     * @return \DateTime
     */
    public static function icalToDate($datestring)
    {
        $datestring = preg_replace('#\s#', '', $datestring);

        // parse date without time
        if (strpos($datestring, 'T') === false) {
            return \DateTime::createFromFormat('Ymd', $datestring);
        }

        // remove time identificator
        $datestring = str_replace('T', '', $datestring);

        // use timezones
        if (strpos($datestring, 'Z') > 0) {

            // use UTC as timezone
            return \DateTime::createFromFormat('YmdHis T', str_replace('Z', ' UTC', $datestring));
        } elseif (strpos($datestring, '+') > 0) {

            // custom timezone
            return \DateTime::createFromFormat('YmdHis e', $datestring);
        }

        // no timezone specified
        return \DateTime::createFromFormat('YmdHis', $datestring);
    } // function


    /**
     * Return a string that is escaped as iCalendar content.
     *
     * @internal
     *
     * @param $string
     *
     * @return string
     */
    public static function escape($string)
    {
        $replace = [
            "/\n/s" => '\n',
            "/,/s"  => '\,',
            "/;/s"  => '\;',
        ];

        return htmlspecialchars(preg_replace(array_keys($replace), $replace, $string));
    } // function
}// class
