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

/**
 * Collection of cache related functions
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
class Cache
{
    /**
     * Generate a prefix for the current cache.
     *
     * @internal
     *
     * @return string
     */
    private static function getPrefix()
    {
        static $prefix;

        if ($prefix === null) {
            $prefix = trim(str_replace('/', '.', __DIR__), '.').'.';
        }

        return $prefix;
    } // function


    /**
     * Fetch a setting from cache. If not exists false is returned.
     *
     * @see apc_fetch
     *
     * @param string $name
     *
     * @return mixed
     */
    public static function fetch($name)
    {
        return apc_fetch(self::getPrefix().$name);
    } // function


    /**
     * Store a new setting in the cache.
     *
     * @see apc_store
     *
     * @param string $name
     * @param mixed $value
     * @param int $ttl (optional)
     *
     * @return mixed
     */
    public static function store($name, $value, $ttl = 0)
    {
        self::delete(self::getPrefix().$name);
        return apc_store(self::getPrefix().$name, $value, $ttl);
    } // function


    /**
     * Delete a key from the Cache
     *
     * @api
     *
     * @see apc_delete
     *
     * @param string  $name
     *
     * @return bool
     */
    public static function delete($name)
    {
        return (bool) apc_delete(self::getPrefix().$name);
    } // function


    /**
     * Tries to decrease the number by $step. If no value is set for the name, guess it was a 0.
     *
     * @api
     *
     * @param string  $name
     * @param int $step (optional)
     *
     * @return bool true on success
     */
    public static function dec($name, $step = 1)
    {
        if (apc_dec(self::getPrefix().$name, $step)) {
            return true;
        }
        return apc_store(self::getPrefix().$name, 0 - $step);
    } // function


    /**
     * Tries to increase the number by $step. If no value is set for the name, guess it was a 0.
     *
     * @api
     *
     * @param string  $name
     * @param int $step (optional)
     *
     * @return bool true on success
     */
    public static function inc($name, $step = 1)
    {
        if (apc_dec(self::getPrefix().$name, $step)) {
            return true;
        }
        return apc_store(self::getPrefix().$name, $step);
    } // function
}// class
