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
 * Notification message repository. Keep all messages that are subject to be send via notification.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @since 1.0
 */
abstract class NotificationMessage extends \FeM\sPof\model\AbstractModelWithId
{

    /**
     * Referenced table.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_notification_message';


    /**
     * @internal
     *
     * @param array $input
     */
    public static function validate(array $input)
    {
        self::getValidator($input)
            ->isNoId('notification_id', _('Ungültige Notification id angegeben.'))
            ->isNoId('protocol_id', _('Ungültige Protokoll id angegeben.'))
            ->isNoId('user_id', _('Ungültige Benutzer id angegeben.'))
            ->validate();
    } // function


    /**
     * Get all messages to be send.
     *
     * @internal for notificationBot only
     *
     * @return array
     */
    public static function getAll()
    {
        $stmt = self::createStatement(
            "
            SELECT
                m.id,
                m.content,
                m.user_id,
                m.from_user_id,
                m.notification_id,
                m.protocol_id,
                n.description
            FROM tbl_notification_message m
            JOIN tbl_notification n ON m.notification_id=n.id
            ORDER BY m.creation ASC
            "
        );

        return $stmt->fetchAll();
    } // function
}// class
