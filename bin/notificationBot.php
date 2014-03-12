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
 *
 * @package FeM\sPof\bin
 * @author dangerground
 * @since 1.0
 */

use FeM\sPof\Application;
use FeM\sPof\Config;
use FeM\sPof\model\Notification;
use FeM\sPof\model\NotificationMessage;
use FeM\sPof\notification\NotificationSend;


// deamonize
$pid = pcntl_fork();
if ($pid === - 1) {
    die('could not fork');
} elseif ($pid > 0) {
    exit;
}

// detatch from the controlling terminal
if (posix_setsid() === - 1) {
    die('could not detach from terminal');
}

// keep the pid
$fp = fopen('/var/run/notificationbot.pid', 'w');
fwrite($fp, posix_getpid());
fclose($fp);

// get mapping for protocol names
$tmp = Notification::getProtocols(false);
$protocol = [];
foreach ($tmp as $t) {
    $protocol[$t['name']] = $t['id'];
}

// set server name for MailUtil
$_SERVER['SERVER_NAME'] = Config::get('base_url');

$handlers = Application::getNotificationHandlers();

$active = true;
while ($active) {
    $queue = NotificationMessage::getAll();
    //echo 'Process next '.count($queue)." Entries\n";

    foreach ($queue as $msg) {
        $success = false;

        switch ($msg['protocol_id']) {
            case $protocol['XMPP']:
                $success = NotificationSend::xmpp();
                break;

            case $protocol['email']:
                $success = NotificationSend::xmpp($msg['user_id'], $msg['description'], $msg['content']);
                break;

            case $protocol['browser']:
                $success = NotificationSend::browser($msg['user_id'], $msg['description'], $msg['content']);
                break;

            default:
                foreach ($handlers as $handler) {
                    if ($protocol[$handler->getType()] == $msg['protocol_id']) {
                        $success = $handler->handleNotification($msg);
                    }
                }
                break;
        } // switch protocol

        // assume message was sent
        if ($success) {
            NotificationMessage::deleteByPk($msg['id']);
        }
    } // foreach

    sleep(5);
} // while
