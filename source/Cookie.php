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

use FeM\sPof\model\LogEvent;
use FeM\sPof\model\User;

/**
 * Collection of functions related to cookies.
 *
 * @package FeM\sPof
 * @author dangerground
 * @author pegro
 * @since 1.0
 */
class Cookie
{
    /**
     * Default config for this class.
     *
     * @api
     *
     * @var array
     */
    private static $defaultConfig = [
        'login' => 'c_login',
        'password' => 'c_pwd'
    ];


    /**
     * Helper function to get the current config.
     *
     * @internal
     *
     * @return array
     */
    private static function getConfig()
    {
        static $config;
        if (!isset($config)) {
            $config = Config::get('cookie', self::$defaultConfig);
        }

        return $config;
    } // function


    /**
     * Login user.
     *
     * @internal
     */
    public static function login()
    {
        $config = self::getConfig();
        if (empty($config['login'])
            || !isset($_COOKIE[$config['login']])
            || empty($config['password'])
            || !isset($_COOKIE[$config['password']])
        ) {

            // return if no login cookie set
            return;
        }
        if (!Session::isLoggedIn()) { // try to login
            $user_id = User::getIdByCredentials($_COOKIE[$config['login']], $_COOKIE[$config['password']], true);
            if ($user_id !== false) {
                $_SESSION['thisuser'] = User::getByPk($user_id);
                Logger::getInstance()->info("login with cookies");
                LogEvent::add([
                    'event' => 'Login.Cookie.Success',
                    'user_id' => $user_id,
                    'reference_parameters' => json_encode([]),
                    'description' => $_SESSION['thisuser']['name'].' logged in (über Cookies)'
                ]);
            } else {
                LogEvent::add([
                    'event' => 'Login.Cookie.Failed',
                    'user_id' => 0,
                    'reference_parameters' => json_encode([]),
                    'description' =>
                        $_COOKIE[$config['login']].' hat sich vergeblich versucht einzuloggen (über Cookies)'
                ]);
                self::deleteLoginCookie();
            }
        } else { // renew
            Logger::getInstance()->info("renew login cookie");
            self::setLoginCookie($_COOKIE[$config['login']], $_COOKIE[$config['password']]);
        }
    } // function


    /**
     * Delete the login cookie
     *
     * @api
     */
    public static function deleteLoginCookie()
    {
        $config = self::getConfig();

        if (!empty($config['login']) && isset($_COOKIE[$config['login']])) {
            Logger::getInstance()->info("delete cookie ".$config['login']);
            setcookie($config['login'], "", time() - 3600, self::getCookiePath());
            unset($_COOKIE[$config['login']]);
        }

        if (!empty($config['password']) && isset($_COOKIE[$config['password']])) {
            Logger::getInstance()->info("delete cookie ".$config['password']);
            setcookie($config['password'], "", time() - 3600, self::getCookiePath());
            unset($_COOKIE[$config['password']]);
        }
    } // function


    /**
     * Set the login cookie with the given credentials.
     *
     * @api
     *
     * @param string $user
     * @param string $pass
     */
    public static function setLoginCookie($user, $pass)
    {
        $config = self::getConfig();
        setcookie($config['login'], $user, time() + 7 * 24 * 3600, self::getCookiePath());
        setcookie($config['password'], $pass, time() + 7 * 24 * 3600, self::getCookiePath());
    } // function


    /**
     * Get the path where to store the cookie on the current domain.
     *
     * @internal
     *
     * @return string
     */
    private static function getCookiePath()
    {
        return Config::get('server', 'path');
    } // function
}// class
