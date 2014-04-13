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

use FeM\sPof\Application;
use FeM\sPof\model\DBConnection;
use FeM\sPof\exception\ModelException;
use FeM\sPof\Logger;
use FeM\sPof\IcalUtil;
use FeM\sPof\model\Event;
use FeM\sPof\model\Group;
use FeM\sPof\model\LogEvent;
use FeM\sPof\model\User;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Property\SupportedCalendarComponentSet;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\Exception\ReportNotSupported;
use Sabre\DAV\Exception\ServiceUnavailable;

/**
 * Handle the calendar backend (requests).
 *
 * @internal
 *
 * @package FeM\sPof\dav
 * @author dangerground
 * @since 1.0
 */
class CalendarBackend extends AbstractBackend
{


    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the calendar. This can be the same as the uri
     *    or a database key.
     *  * uri, which the basename of the uri with which the calendar is accessed.
     *  * principaluri. The owner of the calendar. Almost always the same as principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very common one is '{DAV:}displayname'.
     *
     * @param string $principalUri
     *
     * @return array
     */
    public function getCalendarsForUser($principalUri)
    {
        $user = User::getByName($principalUri);

        $componentSet = new SupportedCalendarComponentSet(['VEVENT']);

        // no user - no calendars
        if ($user === false) {
            return null;
        }

        // build some calendars :)
        $calendars = [];

        // add private user calendar
        $calendars[] = [
            'id' => 'user:'.$user['id'],
            'uri' => $user['name'],
            '{DAV:}displayname' => $user['name'],
            '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Privater Kalender',
            'principaluri' => $principalUri,
            '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => $componentSet,
        ];

        // add group calendars (they may later get split into different calendars for each tag)
        $groups = Group::getByUserId($user['id'], false);
        foreach ($groups as $group) {
            // TODO: split by tags
            $calendars[] = [
                'id' => 'group:'.$group['id'],
                'uri' => $group['shortname'],
                '{DAV:}displayname' => $group['shortname'],
                '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'Kalender für '.$group['name'],
                'principaluri' => $principalUri,
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => $componentSet,
            ];
        }

        $handlers = Application::getCalendarHandlers();
        foreach ($handlers as $handler) {
            $calendar = $handler->getCalendarsForUser($user['id'], $principalUri, $componentSet);

            // one resultset
            if (isset($calendar['id'])) {
                $calendars[] = $calendar;

            // multiple resultsets
            } elseif (isset($calendar[0]) && isset($calendar[0]['id'])) {
                foreach ($calendar as $cal) {
                    $calendar[] = $cal;
                }
            }
        }

        return $calendars;
    } // function


    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference this calendar in other
     * methods, such as updateCalendar.
     *
     * @throws \Sabre\DAV\Exception\Forbidden
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     *
     * @return void
     */
    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        // calendars are always tied to the user account
        Logger::getInstance()->debug('Skipped', [
            'principalUri' => $principalUri,
            'calendarUri' => $calendarUri,
            'properties' => $properties
        ]);
        throw new ReportNotSupported('read-only support');
    } // function


    /**
     * Delete a calendar and all it's objects
     *
     * @param mixed $calendarId
     * @throws \Sabre\DAV\Exception\Forbidden
     * @return void
     */
    public function deleteCalendar($calendarId)
    {
        // calendars are always tied to the user account
        Logger::getInstance()->debug('Skipped', ['calendarId' => $calendarId]);
        throw new ReportNotSupported('read-only support');
    } // function


    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * id - unique identifier which will be used for subsequent updates
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can be any arbitrary string.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.: '  "abcdef"')
     *   * calendarid - The calendarid as it was passed to this function.
     *   * size - The size of the calendar objects, in bytes.
     *
     * Note that the etag is optional, but it's highly encouraged to return for speed reasons.
     *
     * The calendardata is also optional. If it's not returned 'getCalendarObject' will be called later, which *is*
     * expected to return calendardata.
     *
     * If neither etag or size are specified, the calendardata will be used/fetched to determine these numbers. If
     * both are specified the amount of times this is needed is reduced by a great degree.
     *
     * @param mixed $calendarId
     *
     * @return array
     */
    public function getCalendarObjects($calendarId)
    {
        $source = explode(':', $calendarId);
        $result = [];
        switch ($source[0]) {

            // Group event
            case 'group':
                $events = Event::getByGroupId($source[1]);
                foreach ($events as $event) {
                    $tmp = $this->getCalendarObject($calendarId, 'event.'.$event['id'].'.ics');
                    if ($tmp !== false) {
                        $result[] = $tmp;
                    }
                }
                break;

            // private user calendar
            case 'user':
                $events = Event::getByUserId($source[1]);
                foreach ($events as $event) {
                    $tmp = $this->getCalendarObject($calendarId, 'event.'.$event['id'].'.ics');
                    if ($tmp !== false) {
                        $result[] = $tmp;
                    }
                }
                break;

            default:
                $handlers = Application::getCalendarHandlers();
                foreach ($handlers as $handler) {
                    $events = $handler->getEventsByCalendarId($calendarId);
                    foreach ($events as $event) {
                        $tmp = $this->getCalendarObject($calendarId, 'event.'.$event['id'].'.ics');
                        if ($tmp !== false) {
                            $result[] = $tmp;
                        }
                    }
                }
        }

        return $result;
    } // function


    /**
     * Returns information from a single calendar object, based on it's object uri.
     *
     * The returned array must have the same keys as getEventsByUserId. The 'calendardata' object is required here
     * though, while it's not required for getEventsByUserId.
     *
     * This method must return null if the object did not exist.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     *
     * @return array|null
     */
    public function getCalendarObject($calendarId, $objectUri)
    {
        $event = self::getEventFromObjectUri($objectUri);
        if ($event === false) {
            return false;
        }

        // build calendar data
        $calendarData = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'BEGIN:VEVENT',
            'UID:'.self::eventToUid($event),
            'CREATED:'.date('Ymd\THis\Z', strtotime($event['creation'])),
            'DTSTART:'.date('Ymd\THis\Z', strtotime($event['beginning'])),
            'SUMMARY:'.IcalUtil::escape($event['title']),
            'CLASS:'.($event['public'] ? 'PUBLIC' : 'PRIVATE'),
        ];

        if (isset($event['ending'])) {
            $calendarData[] = 'DTEND:'.date('Ymd\THis\Z', strtotime($event['ending']));
        } elseif ($event['length'] !== '00:00:00') {
            $calendarData[] = 'DURATION:'.\DateInterval::createFromDateString('P'
                    .($interval[0] != '00' ? $interval[0].'H' : '')
                    .($interval[1] != '00' ? $interval[1].'M' : '')
                    .($interval[2] != '00' ? $interval[2].'S' : '')
                );
        }


        if (!empty($event['repeat'])) {
            $calendarData[] = 'RRULE:'.$event['repeat'];
        }
        if (!empty($event['description'])) {
            $calendarData[] = 'DESCRIPTION:'.IcalUtil::escape($event['description']);
        }
        if (!empty($event['locality'])) {
            $calendarData[] = 'LOCATION:'.IcalUtil::escape($event['locality']);
        }

        // close calendar
        $calendarData[] = 'END:VEVENT';
        $calendarData[] = 'END:VCALENDAR';

        $event['calendardata'] = implode("\n", $calendarData);

        return [
            'id'            => 'event:'.$event['id'],
            'uri'           => 'event.'.$event['id'].'.ics',//self::eventToUid($event).'.ics',
            'lastmodified'  => $event['modify'],
            'etag'          => '"'.md5($event['calendardata']).'"',
            'calendarid'    => $calendarId,
            'size'          => strlen($event['calendardata']),
            'calendardata'  => $event['calendardata'],
            'component'     => 'vevent',
         ];
    } // function


    /**
     * Creates a new calendar object.
     *
     * It is possible return an etag from this function, which will be used in the response to this PUT request. Note
     * that the ETag must be surrounded by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the calendar-data. If the result of a
     * subsequent GET to this object is not the exact same as this request body, you should omit the ETag.
     *
     * @throws \Sabre\DAV\Exception\ServiceUnavailable
     * @throws \Sabre\DAV\Exception\ReportNotSupported
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     *
     * @return string|null
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        Logger::getInstance()->debug('Skipped', ['objectUri' => $objectUri]);
        DBConnection::getInstance()->beginTransaction();

        $source = explode(':', $calendarId);
        $group_id = 0;
        switch ($source[0]) {
            case 'group':
                $group_id = $source[1];
                break;

            default:
                throw new ReportNotSupported('This is a read-only calendar.');
                break;
        }

        $user_id = self::getCurrentUserId();

        $event = IcalUtil::readEventFromString($calendarData);

        // set values
        $input = [
            'original_uid' => $event['UID'],
            'group_id' => $group_id,
            'user_id' => $user_id,
            'title' => $event['SUMMARY'],
            'description' => (!empty($event['DESCRIPTION']) ? $event['DESCRIPTION'] : null),
            'locality' => (!empty($event['LOCATION']) ? $event['LOCATION'] : null),
            'beginning' => IcalUtil::icalToDate($event['DTSTART']),
            'length' => '0 seconds',
            'repeat' => (!empty($event['RRULE']) ? $event['RRULE'] : null),
            'visible' => true,
            'disabled' => false
        ];

        // use creted and last-modified, if they're set
        if (isset($event['CREATED'])) {
            $input['creation'] = IcalUtil::icalToDate($event['CREATED']);

            // if we had a last-modified, but no creation date, the check would fail because creation=now and
            // last-modified will always be in the past, so only use last-modified, iff  created is set
            if (isset($event['LAST-MODIFIED'])) {
                $input['modify'] = IcalUtil::icalToDate($event['LAST-MODIFIED']);
            }
        }
        // use class for determining public status (use private for everything else)
        if (isset($event['CLASS'])) {
            $input['public'] = ($event['CLASS'] == 'PUBLIC');
        } else {
            // default is public
            $input['public'] = true;
        }

        try {
            $event_id = Event::add($input);

            LogEvent::add(
                [
                    'event' => 'Dav.Caldav.CreateCalendarObject.Success',
                    'user_id' => $user_id,
                    'reference_parameters' => json_encode(['calendarId' => $calendarId, 'event_id' => $event_id]),
                    'description' => 'Event wurde via remote erstellt.'
                ]
            );
        } catch (ModelException $e) {
            throw new ServiceUnavailable("Unknown Problem");
        }

        DBConnection::getInstance()->commit();

        return '"'.md5($calendarData).'"';
    } // function


    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * It is possible return an etag from this function, which will be used in the response to this PUT request. Note
     * that the ETag must be surrounded by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the calendar-data. If the result of a
     * subsequent GET to this object is not the exact same as this request body, you should omit the ETag.
     *
     * @throws \Sabre\DAV\Exception\ServiceUnavailable
     * @throws \Sabre\DAV\Exception\ReportNotSupported
     * @throws \Sabre\DAV\Exception\PreconditionFailed
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     *
     * @return string|null
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {

        DBConnection::getInstance()->beginTransaction();

        $source = explode(':', $calendarId);
        switch ($source[0]) {
            case 'user':
            case 'group':
                break;

            default:
                throw new ReportNotSupported('This is a read-only calendar.');
                break;
        }

        $user_id = self::getCurrentUserId();

        $old_event = self::getEventFromObjectUri($objectUri, true);
        if ($old_event['user_id'] !== $user_id) {
            throw new PreconditionFailed("You're not the owner of the event, you're trying to edit.");
        }

        $event = IcalUtil::readEventFromString($calendarData);

        // set values
        $input = [
            'title' => $event['SUMMARY'],
            'description' => (!empty($event['DESCRIPTION']) ? $event['DESCRIPTION'] : null),
            'locality' => (!empty($event['LOCATION']) ? $event['LOCATION'] : null),
            'beginning' => IcalUtil::icalToDate($event['DTSTART']),
            'repeat' => (!empty($event['RRULE']) ? $event['RRULE'] : null),
        ];

        // use creted and last-modified, if they're set
        if (isset($event['CREATED'])) {
            $input['creation'] = IcalUtil::icalToDate($event['CREATED']);

            // if we had a last-modified, but no creation date, the check would fail because creation=now and
            // last-modified will always be in the past, so only use last-modified, iff  created is set
            if (isset($event['LAST-MODIFIED'])) {
                $input['modify'] = IcalUtil::icalToDate($event['LAST-MODIFIED']);
            }
        }
        // use class for determining public status (use private for everything else)
        if (isset($event['CLASS'])) {
            $input['public'] = ($event['CLASS'] == 'PUBLIC');
        }

        try {
            $event_id = Event::updateByPk($old_event['id'], $input);

            LogEvent::add(
                [
                    'event' => 'Dav.Caldav.CreateCalendarObject.Success',
                    'user_id' => $user_id,
                    'reference_parameters' => json_encode(['calendarId' => $calendarId, 'event_id' => $event_id]),
                    'description' => 'Event wurde via remote erstellt.'
                ]
            );
        } catch (ModelException $e) {
            throw new ServiceUnavailable("Unknown Problem");
        }

        DBConnection::getInstance()->commit();

        return '"'.md5($calendarData).'"';
    } // function


    /**
     * Deletes an existing calendar object.
     *
     * @throws \Sabre\DAV\Exception\ReportNotSupported
     * @throws \Sabre\DAV\Exception\PreconditionFailed
     *
     * @param mixed $calendarId
     * @param string $objectUri
     *
     * @return void
     */
    public function deleteCalendarObject($calendarId, $objectUri)
    {
        DBConnection::getInstance()->beginTransaction();

        $source = explode(':', $calendarId);
        switch ($source[0]) {
            case 'user':
            case 'group':
                break;

            default:
                throw new ReportNotSupported('This is a read-only calendar.');
                break;
        }

        $user_id = self::getCurrentUserId();

        $event = self::getEventFromObjectUri($objectUri, true);
        if ($event['user_id'] !== $user_id) {
            throw new PreconditionFailed("You're not the owner of the event, you're trying to delete.");
        }
        Event::updateByPk(
            $event['id'],
            [
                'visible' => false,
                "disabled" => true
            ]
        );

        LogEvent::add(
            [
                'event' => 'Dav.Caldav.DeleteCalendarObject.Success',
                'user_id' => $user_id,
                'reference_parameters' => json_encode(['calendarId' => $calendarId, 'event_id' => $event['id']]),
                'description' => 'Event wurde via remote gelöscht.'
            ]
        );
        DBConnection::getInstance()->commit();
    } // function


    /**
     * Get user id of the acting user. As we have no normal Session context, we need another way to find the current
     * user. Do that by borrowing the Authorization User from HTTP-Header. We only get here if the authentication was
     * a success, so this user should be valid, otherwise we're screwed and the function will return false.
     *
     * @return int|bool current user id
     */
    public static function getCurrentUserId()
    {
        $user = User::getByName($_SERVER['PHP_AUTH_USER']);
        if ($user !== false) {
            return $user['id'];
        }

        return false;
    } // function


    /**
     * Get or create uid from event array.
     *
     * @param array $event at least with keys original_uid, id and creation
     *
     * @return string
     */
    public static function eventToUid($event)
    {
        // use uid if present
        if (!empty($event['original_uid'])) {
            return $event['original_uid'];
        }

        // otherwise construct a globally unique id
        return $event['creation'].'-'.$event['id'].'@'.$_SERVER['SERVER_NAME'];
    } // function


    /**
     * Return event object if it can be found in the Database. Retrieve the dataset by original UID or our event id.
     *
     * @throws \Sabre\DAV\Exception\NotFound when no related event is found
     *
     * @param $objectUri
     * @param bool $throwing
     *
     * @return array|bool event when successful, false otherwise
     */
    public static function getEventFromObjectUri($objectUri, $throwing = false)
    {
        if (strpos($objectUri, 'event.') === 0) {
            $event = Event::getByPk(str_replace(['event.', '.ics'], '', $objectUri));
        } else {
            $event = Event::getByOriginalUid(str_replace('.ics', '', $objectUri));
        }

        // check if we really got an instance
        if ($event === false && $throwing === true) {
            throw new NotFound("The referenced objectUri could not be found");
        }

        return $event;
    } // function
}// class
