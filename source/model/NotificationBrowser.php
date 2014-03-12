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
 * Notification browser repository. Keeps all messages that are subject to be send via html5 notification.
 *
 * @internal
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @since 1.0
 */
abstract class NotificationBrowser extends AbstractModelWithId
{
    /**
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_notification_browser';


    /**
     * @internal
     *
     * @param array $input
     */
    public static function validate(array $input)
    {
        // nothing to be validated
    } // function


    /**
     * Get next message for user.
     *
     * @internal
     *
     * @param int $user_id
     * @return false|array
     */
    public static function getNextByUserId($user_id)
    {
        $stmt = self::createStatement(
            "
            SELECT
                id,
                content,
                title
            FROM tbl_notification_browser
            WHERE user_id=:user_id
            ORDER BY creation ASC
            LIMIT 1
            "
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetch();
    } // function
}// class
