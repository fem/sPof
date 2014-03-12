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

use FeM\sPof\Application;
use FeM\sPof\Logger;

/**
 * Handle a Xmpp Connection.
 *
 * @package FeM\sPof\notification
 * @author dangerground
 * @since 1.0
 */
class XmppConnection
{

    /**
     * Default config.
     *
     * @api
     *
     * @var array
     */
    private static $defaultConfig = [
        'jid' => 'user',
        'pass' => 'pass',
        'auth_type' => 'DIGEST_MD5',
        'host' => 'localhost',
        'port' => 5222,
        'force_tls' => false,
        'resource' => 'spofBot',
        'log_path' => 'tmp/jaxl.log',
        'log_level' => JAXL_DEBUG,
    ];

    /**
     * Reference to the current XMPP Connection
     *
     * @var \JAXL
     */
    protected $connection;

    /**
     * How often to call the periodic function.
     *
     * @internal
     *
     * @var int
     */
    private $time;

    /**
     * Function to call periodically.
     *
     * @internal
     *
     * @var callback
     */
    private $function;


    /**
     * Get a instance.
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new XmppConnection();
        }
        return $instance;
    } // function


    /**
     * Create new instance.
     */
    private function __construct()
    {
        self::$defaultConfig['log_path'] .= Application::$FILE_ROOT.self::$defaultConfig['log_path'];
        $config = \FeM\sPof\Config::get('xmpp_config', self::$defaultConfig);


        // set connection params
        $this->connection = new \JAXL($config);

        // load extensions
        $this->connection->require_xep([
            '0203' // delayed delivery
        ]);


        // on success add periodic function call
        $this->connection->add_cb('on_auth_success', [$this, 'onAuthSuccess']);

        // on auth failure exit
        $this->connection->add_cb('on_auth_failure', [$this, 'onAuthFailure']);
    } // constructor


    /**
     * start handling loop (does not end)
     *
     * @api
     */
    public function run()
    {
        // start connection
        $this->connection->start([
            '--with-unix-sock' => true
        ]);
    } // function


    /**
     * Which action should be performed if a authorization failed.
     *
     * @internal
     *
     * @param string $reason
     */
    public function onAuthFailure($reason)
    {
        Logger::getInstance()->info('got on_auth_failure cb with reason: '.$reason);
        $this->connection->send_end_stream();
    } // function


    /**
     * We're online!
     *
     * @internal
     */
    public function onAuthSuccess()
    {
        Logger::getInstance()->info("got on_auth_success cb, jid: ".$this->connection->full_jid->to_string());
        $this->connection->set_status("available!", "dnd", 10);
        \JAXLLoop::$clock->call_fun_periodic($this->time, $this->function, $this->connection);
    } // function


    /**
     * Runs the given function periodocally
     *
     * @internal
     *
     * @param int $time time in msec
     * @param mixed $function string to function or array to object-method
     */
    public function callPeriodic($time, $function)
    {
        $this->time = $time;
        $this->function = $function;
    } // function


    /**
     * Send a new message via XMPP.
     *
     * @param string $target jid
     * @param string $message
     */
    public function sendMessage($target, $message)
    {
        $msg = new \XMPPMsg(['to' => $target, 'from' => $this->connection->full_jid->to_string()], $message);
        $this->connection->send($msg);
    } // function
}// class
