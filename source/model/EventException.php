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

namespace FeM\sPof\model;

/**
 * This class can be used to deploy single exceptions to a repeat rule of an event. It does not define alternative
 * times, these can be need added with the EventDate class.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @since 1.0
 */
abstract class EventException extends AbstractModel
{

    /**
     * Referenced table.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_event_course_exception';


    /**
     * @internal
     *
     * @param array $input
     */
    protected static function validate(array $input)
    {
        // nothing to validate
    } // function


    /**
     * Delete all exceptions for an event.
     *
     * @api
     *
     * @param int $event_id
     * @return bool
     */
    public static function deleteByEventId($event_id)
    {
        $stmt = self::createStatement(
            "
            DELETE
            FROM tbl_event_exception
            WHERE event_id = :event_id
            "
        );
        $stmt->assignId('event_id', $event_id);

        return $stmt->affected();
    } // function deleteByEventId
}// class
