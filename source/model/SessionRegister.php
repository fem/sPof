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

use FeM\sPof\Config;
use FeM\sPof\Cache;

/**
 * List of registered sessions, for e.g. whoisonline.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @since 1.0
 */
class SessionRegister
{

    /**
     * Default config.
     *
     * @api
     *
     * @var array
     */
    private static $defaultConfig = [
        'min_update' => 300, // seconds
        'max_inactive' => 15, // minutes
    ];


    /**
     * Prefix for caching.
     *
     * @internal
     *
     * @var string
     */
    const PREFIX = 'whoisonline.';


    /**
     * Add a new user to the list.
     *
     * @internal
     *
     * @param array $user needs at least 'id' and 'name' fields
     *
     * @return bool true if user is registered
     */
    public static function add(array $user)
    {
        if (empty($user['id'])) {
            return false;
        }

        // get current list of users and add the new one
        $users = Cache::fetch(self::PREFIX.'users');
        if ($users === false) {
            $users = [];
        }
        $users[$user['id']] = time();

        // save list of current users, the total number of currently online users
        // and setting for the current user
        $config = Config::get('whoisonline', self::$defaultConfig);
        Cache::store(self::PREFIX.'users_updated', time(), $config['min_update']);
        Cache::store(self::PREFIX.'users', $users);

        return true;
    } // function


    /**
     * Remove all outdated entries from list.
     *
     * @internal
     */
    private static function cleanup()
    {
        $config = Config::get('whoisonline', self::$defaultConfig);

        // check if we have to update again
        $updated = Cache::fetch(self::PREFIX.'users_updated');
        if ($updated !== false && $updated < strtotime('-'.$config['min_update'].' seconds')) {
            return;
        }

        $users = Cache::fetch(self::PREFIX.'users');
        if (empty($users)) {
            return;
        }

        // remove old entries
        foreach ($users as $user_id => $time) {
            if ($time < strtotime('-'.$config['max_inactive'].' minutes')) {
                unset($users[$user_id]);
            }
        }

        Cache::store(self::PREFIX.'users', $users);
        Cache::store(self::PREFIX.'users_updated', time(), $config['min_update']);
    } // function


    /**
     * Remove entry from array. Returns only false, if the entry still exists after this action.
     *
     * @internal
     *
     * @param array $user
     *
     * @return bool true if registered name does now no longer exist
     */
    public static function delete(array $user)
    {
        if (empty($user['id'])) {
            return true;
        }

        // if there is no entry, it is already unregistered
        if (!$users = Cache::fetch(self::PREFIX.'users')) {
            return true;
        }

        // unset
        unset($users[$user['id']]);
        Cache::store(self::PREFIX.'users', $users);
        return true;
    } // function


    /**
     * Get an array with all users online and their login time
     *
     * @api
     *
     * @return array
     */
    public static function getOnlineUsers()
    {
        self::cleanup();
        return Cache::fetch(self::PREFIX.'users');
    } // function


    /**
     * Get the count of users online
     *
     * @api
     *
     * @return int
     */
    public static function getOnlineUsersCount()
    {
        self::cleanup();
        return count(Cache::fetch(self::PREFIX.'users'));
    } // function


    /**
     * Check if a user is online
     *
     * @api
     *
     * @param int $user_id
     * @return bool
     */
    public static function isUserOnline($user_id)
    {
        self::cleanup();
        return isset(Cache::fetch(self::PREFIX.'users')[$user_id]);
    } // function
}// class
