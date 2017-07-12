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

namespace FeM\sPof;

use FeM\sPof\model\SessionRegister;
use FeM\sPof\model\User;

/**
 * Session representing the current user.
 *
 * @package FeM\sPof
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
class Session
{

    /**
     * Default config.
     *
     * @api
     *
     * @var array
     */
    private static $defaultConfig = [
        'name' => 'usi',
        'impersonate' => false,
    ];

    /**
     * Current list of saved errors.
     *
     * @internal
     *
     * @var array
     */
    private static $errors = [];


    /**
     * Get a instance.
     *
     * @api
     *
     * @return Session
     */
    public static function getInstance()
    {
        static $_instance;
        if (!isset($_instance)) {
            $_instance = new Session();
            Cookie::login();
            $user = self::getUser();
            if ($user !== null) {
                SessionRegister::add($user);
            }
        }
        return $_instance;
    } // function


    /**
     * Create new instance.
     *
     * @internal
     */
    private function __construct()
    {
        ini_set('arg_separator.output', '&amp;');
        ini_set('session.name', self::getConfig('name'));
        ini_set('session.use_only_cookies', true);
        ini_set('session.use_trans_sid', false);
        ini_set('session.cookie_path', Config::getDetail('server','path'));
        $expire = session_cache_expire();
        if (session_id() == "") {
            session_start();
        }
        $_SESSION['expire'] = $expire;
    } // constructor


    /**
     * Get current config setting.
     *
     * @internal
     *
     * @param $name
     * @return array
     */
    private static function getConfig($name)
    {
        static $config;
        if (!isset($config)) {
            $config = Config::get('session', self::$defaultConfig);
        }
        return $config[$name];
    }


    /**
     * Delete the current Session settings.
     *
     * @api
     */
    public function reset()
    {
        session_unset();
        session_destroy();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000);
        }
        $_SESSION = [];
        $expire = session_cache_expire();
        ini_set('session.cookie_path', Config::getDetail('server','path'));
        session_start();
        session_regenerate_id(true);
        $_SESSION['expire'] = $expire;
        unset($_SESSION['thisuser']);
        Logger::getInstance()->info("reset session ");
    } // function


    /**
     * Impersonate another user by using his user_id everywhere.
     *
     * @api
     *
     * @param $user_id
     */
    public static function impersonate($user_id)
    {

        if (!self::getConfig('impersonate')) {
            return;
        }
        self::getInstance();

        // do not allow transitional impersonization
        if (isset($_SESSION['thisuser']['real_id'])) {
            $real_id = $_SESSION['thisuser']['real_id'];
        } else {
            $real_id = self::getUserId();
        }
        $_SESSION['thisuser'] = User::getByPk($user_id);
        $_SESSION['thisuser']['real_id'] = $real_id;
        $_SESSION['thisuser']['impersonated'] = time();

        Logger::getInstance()->info("impersonate user");
    } // function reset


    /**
     * Get the settings of the current user.
     *
     * @api
     *
     * @return array|null
     */
    public static function getUser()
    {
        if (isset($_SESSION['thisuser'])) {
            if (self::getConfig('impersonate')
                && isset($_SESSION['thisuser']['impersonated'])
                && ($_SESSION['thisuser']['impersonated'] < time() - 600)
            ) {
                $_SESSION['thisuser'] = User::getByPk($_SESSION['thisuser']['real_id']);
                $_SESSION['thisuser']['real_id'] = $_SESSION['thisuser']['id'];
                unset($_SESSION['thisuser']['impersonated']);
            }

            return $_SESSION['thisuser'];
        }
        return null;
    } // function


    /**
     * Check if the current user is logged in.
     *
     * @api
     *
     * @return bool
     */
    public static function isLoggedIn()
    {
        $user = self::getUser();
        if (is_array($user) && !empty($user['id'])) {
            return true;
        }
        return false;
    } // function isLoggedIn


    /**
     * Get the current user id.
     *
     * @api
     *
     * @return int|null
     */
    public static function getUserId()
    {
        $user = self::getUser();
        if (is_array($user) && !empty($user['id'])) {
            return $user['id'];
        }
        return null;
    } // function getUserId


    /**
     * Add a new error message to the current users log.
     *
     * @api
     *
     * @param string $content
     * @param string $field (optional)
     */
    public static function addErrorMsg($content, $field = null)
    {
        self::$errors[] = ['field' => $field, 'content' => $content];
    } // function


    /**
     * Add a new success message to the session.
     *
     * @api
     *
     * @param string $content
     */
    public static function addSuccessMsg($content)
    {
        if (strlen($content)) {
            $_SESSION['messages']['success'][] = $content;
        }
    } // function


    /**
     * Get the error messages for the current session.
     *
     * @api
     *
     * @return array
     */
    public static function getErrorMsg()
    {
        return self::$errors;
    } // function


    /**
     * Get the success messages for the current session.
     *
     * @api
     *
     * @return array
     */
    public static function getSuccessMsg()
    {
        Session::getInstance();
        if (isset($_SESSION['messages']['success'])) {
            return $_SESSION['messages']['success'];
        }
        return [];
    } // function


    /**
     * Delete all error messages from the current session.
     */
    public static function resetErrorMsg()
    {
        self::$errors = [];
    } // function


    /**
     * Delete all success messages from the current session.
     */
    public static function resetSuccessMsg()
    {
        Session::getInstance();
        unset($_SESSION['messages']['success']);
    } // function

    /**
     * Get parameter stored in session storage
     */
    public static function get($name, $detail = null)
    {
        Session::getInstance();
        if(isset($_SESSION['data']) && isset($_SESSION['data'][$name])) {
            if($detail == null) {
                return $_SESSION['data'][$name];
            } elseif(isset($_SESSION['data'][$name][$detail])) {
                return $_SESSION['data'][$name][$detail];
            }
        }
        return null;
    } // function

    /**
     * Get parameter stored in session storage
     */
    public static function set($name, $value = null, $detail = null)
    {
        Session::getInstance();
        if(!isset($_SESSION['data'])) {
            $_SESSION['data'] = [];
        }

        if($detail == null) {
            if($value === null) {
                unset($_SESSION['data'][$name]);
            } else {
                $_SESSION['data'][$name] = $value;
            }
        } elseif(isset($_SESSION['data'][$name][$detail]) && $value === null) {
            unset($_SESSION['data'][$name][$detail]);
        } else {
            $_SESSION['data'][$name][$detail] = $value;
        }

        return true;
    } // function
}// class
