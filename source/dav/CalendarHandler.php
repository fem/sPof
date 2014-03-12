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

namespace FeM\sPof\dav;

/**
 * Interface to handle calendar data.
 *
 * @package FeM\sPof\dav
 * @author dangerground
 * @since 1.0
 */
abstract class CalendarHandler
{

    /**
     * Return a list of calendars for a user.
     *
     * @api
     *
     * @param int $user_id
     * @param string $principalUri
     * @param string $componentSet
     *
     * @return array
     */
    abstract public function getCalendarsForUser($user_id, $principalUri, $componentSet);


    /**
     * Get events for a specific calendar.
     *
     * @api
     *
     * @param string $calendar_id
     *
     * @return array
     */
    abstract public function getEventsByCalendarId($calendar_id);


    /**
     * Return a calendar object in a predefined structure..
     *
     * @internal
     *
     * @param int $id
     * @param string $uri
     * @param string $name
     * @param string $principalUri
     * @param string $componentSet
     *
     * @return array
     */
    protected function buildCalendarObject($id, $uri, $name, $principalUri, $componentSet)
    {
        return [
            'id' => $id,
            'uri' => $uri,
            '{DAV:}displayname' => $name,
            'principaluri' => $principalUri,
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => $componentSet,
        ];
    }
}// class
