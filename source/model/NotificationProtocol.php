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
 * Notification protocol repository.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @since 1.0
 */
abstract class NotificationProtocol extends AbstractModel
{
    /**
     * Referenced table
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'rel_user_notification_protocol';


    /**
     * @internal
     *
     * @param array $input
     */
    public static function validate(array $input)
    {
        self::getValidator($input)
            ->isNoId('notification_id', _s('Ungültige Notification id angegeben.'))
            ->isNoId('protocol_id', _s('Ungültige Protokoll id angegeben.'))
            ->isNoId('user_id', _s('Ungültige Benutzer id angegeben.'))
            ->isNotBool('used', _s('Ungültige Benutzer id angegeben.'))
            ->validate();
    } // function


    /**
     * Delete all personal protocol settings for a user.
     *
     * @api
     *
     * @param int $user_id
     * @return bool
     */
    public static function deleteByUserId($user_id)
    {
        $stmt = self::createStatement(
            "
            DELETE
            FROM rel_user_notification_protocol
            WHERE user_id=:user_id
            "
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->affected();
    } // function


    /**
     * Check if a notfication can be done via the protocol.
     *
     * @api
     *
     * @param int $notification_id
     * @param int $protocol_id
     *
     * @return boolean
     */
    public static function isAllowed($notification_id, $protocol_id)
    {
        $stmt = self::createStatement(
            "
            SELECT allowed
            FROM rel_notification_protocol
            WHERE
                notification_id=:notification_id
                AND protocol_id=:protocol_id
            "
        );
        $stmt->assignId('notification_id', $notification_id);
        $stmt->assignId('protocol_id', $protocol_id);

        return $stmt->fetchColumn();
    } // function


    /**
     * Get all protocol settings of a notification for a user.
     *
     * @api
     *
     * @param int $notification_id
     * @param int $user_id
     *
     * @return array
     */
    public static function getByUserId($notification_id, $user_id)
    {

        $stmt = self::createStatement(
            "
            SELECT
                p.id,
                p.name AS protocol,
                COALESCE(u.used, r.allowed, false) AS used,
                COALESCE(r.allowed, true) AS allowed
            FROM tbl_notification_protocol p
            LEFT JOIN rel_notification_protocol r
                ON r.notification_id=:notification_id
                AND r.protocol_id = p.id
            LEFT JOIN rel_user_notification_protocol u
                ON u.notification_id=:notification_id
                AND u.user_id=:user_id
                AND u.protocol_id=p.id
            WHERE
                p.disabled IS FALSE
                AND p.visible IS TRUE
            "
        );
        $stmt->assignId('notification_id', $notification_id);
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchAll();
    } // function
}// class
