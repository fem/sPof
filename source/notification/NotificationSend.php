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

namespace FeM\sPof\notification;

use FeM\sPof\Config;
use FeM\sPof\model\Notification;
use FeM\sPof\model\NotificationBrowser;
use FeM\sPof\model\User;
use FeM\sPof\template\HtmlTemplate;

/**
 * Util to send notifications via different target channels.
 *
 * @internal
 *
 * @package FeM\sPof\notification
 * @author dangerground
 * @since 1.0
 */
abstract class NotificationSend
{
    /**
     * Send notification via xmpp.
     *
     * @internal
     *
     * @return bool success
     */
    public static function xmpp()
    {
        //$xmpp->send(new XMPPMsg(array('to'=>$message['target'], 'from'=>$connection->full_jid->to_string()),
        //$message['content']));
    } // function


    /**
     * Send notification via email.
     *
     * @param int $user_id
     * @param string $title
     * @param string $message
     *
     * @return bool success
     */
    public static function email($user_id, $title, $message)
    {
        $server = Config::get('server');

        $user = User::getByPk($user_id);
        $template = HtmlTemplate::getInstance();
        $template->assign('firstname', $user['firstname']);
        $template->assign('basedir', $server['url'].$server['path']);
        $template->assign('content', $message);

        return MailUtil::send(
            Notification::getEmailAddressByUserId($user_id),
            $title,
            $template->fetch('mail/notification.tpl')
        );
    } // function


    /**
     * Send notification via browser.
     *
     * @param int $user_id
     * @param string $title
     * @param string $message
     *
     * @return bool success
     */
    public static function browser($user_id, $title, $message)
    {
        return NotificationBrowser::add(
            [
                'user_id' => $user_id,
                'title' => $title,
                'content' => $message
            ]
        );
    } // function
}// class
