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
 * Notification repository. Possible notification channels.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @since 1.0
 */
abstract class Notification extends AbstractModelWithId
{

    /**
     * Referenced Table.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_notification_message';


    /**
     * Get a notification id.
     *
     * @api
     *
     * @param string $notification
     *
     * @return string
     */
    public static function getIdByName($notification)
    {
        if (empty($notification)) {
            return false;
        }

        $stmt = self::createStatement(
            "
            SELECT id
            FROM tbl_notification
            WHERE name = :notification
            "
        );
        $stmt->assign('notification', $notification);

        return $stmt->fetchColumn();
    } // function


    /**
     * Returns the default target group for a specific notification. Returns empty string if there is no setting in the
     * database.
     *
     * @api
     *
     * @param string $notification
     *
     * @return string
     */
    public static function getTarget($notification)
    {
        if (empty($notification)) {
            return false;
        }

        $stmt = self::createStatement(
            "
            SELECT target_group
            FROM tbl_notification
            WHERE name <@ :notification
            "
        );
        $stmt->assign('notification', $notification);

        return $stmt->fetchColumn();
    } // function


    /**
     * Get all notifications.
     *
     * @api
     *
     * @param bool $showActive (optional) if inactive also hidden notifications are shown
     *
     * @return array
     */
    public static function getProtocols($showActive = true)
    {
        $stmt = self::createStatement(
            "
            SELECT
                id,
                name,
                description
            FROM tbl_notification_protocol". ($showActive ? "
            WHERE
                disabled IS FALSE
                AND visible IS TRUE" : '')
        );

        return $stmt->fetchAll();
    } // function


    /**
     * Get all notifications.
     *
     * @api
     *
     * @return array
     */
    public static function getAll()
    {
        $stmt = self::createStatement(
            "
            SELECT
                id,
                name,
                description,
                target_group
            FROM tbl_notification
            "
        );

        return $stmt->fetchAll();
    } // function


    /**
     * Return XMPP-Adress for User by id.
     *
     * @param $user_id
     *
     * @return bool|string false if nothing was found
     */
    /*
    public static function getXmppAddressByUserId($user_id)
    {
        $stmt = self::createStatement("
            SELECT profile_messenger_jabber
            FROM tbl_user
            WHERE id=:user_id");
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchColumn();
    } // function
    */


    /**
     * Return E-Mail-Address for User by id.
     *
     * @api
     *
     * @param $user_id
     *
     * @return bool|string false if nothing was found
     */
    public static function getEmailAddressByUserId($user_id)
    {
        $stmt = self::createStatement(
            "
            SELECT email
            FROM tbl_user
            WHERE id=:user_id
            "
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchColumn();
    } // function
}// class
