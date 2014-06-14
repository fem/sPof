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

namespace FeM\sPof\view;

use FeM\sPof\Session;
use FeM\sPof\model\NotificationBrowser;

/**
 * Handle JS Notification requests.
 *
 * @package FeM\sPof\view
 * @author dangerground
 * @since 1.0
 */
class RpcNotificationView extends \FeM\sPof\view\AbstractRawView
{
    private static $RETRY = 10; // seconds
    private static $TIMEOUT = 10; // seconds
    private static $KEEP_ALIVE = 300; // seconds

    /**
     * Get an update for the current user.
     */
    public function update()
    {
        if (!Session::isLoggedIn()) {
            exit;
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Access-Control-Allow-Origin: *');

        $lastEventId = floatval(isset($_SERVER['HTTP_LAST_EVENT_ID']) ? $_SERVER['HTTP_LAST_EVENT_ID'] : 0);
        if ($lastEventId == 0) {
            $lastEventId = floatval(isset($_GET['lastEventId']) ? $_GET['lastEventId'] : 0);
        }


        // 2kB padding for IE
        // @codingStandardsIgnoreStart
#        echo ':'.str_repeat(' ', 2048)."\n";
        echo 'retry: '.(self::$RETRY * 1000). PHP_EOL;

        // event-stream
        $started = time();
        //while (true) {
            $notification = NotificationBrowser::getNextByUserId(Session::getUserId());
            if ($notification !== false) {
                $data = [
                    'type' => 'notification',
                    'title' => $notification['title'],
                    'payload' => $notification['content']
                ];
                NotificationBrowser::deleteByPK($notification['id']);

                $lastEventId++;
                echo 'id: '.$lastEventId . PHP_EOL;
                echo 'data: '.str_replace("\n", "\ndata: ", json_encode($data)) . PHP_EOL.PHP_EOL;

                ob_flush();
                flush();
                // @codingStandardsIgnoreEnd
//            } elseif ((time() - $started) % self::$KEEP_ALIVE == 0) {
//                // send keep alive comment
//                echo ': '.sha1(mt_rand())."\n\n";
            }

//            if (time() - $started > self::$TIMEOUT) {
//                break;
//            }
        //    usleep(self::$RETRY * 1000000);
        //}
        exit;
    } // function
}// class
