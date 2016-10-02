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
     * @var internal cache structure when APC is not available or disabled
     */
    private $data = [];

    /**
     * Get the instance.
     *
     * @api
     *
     * @return Cache
     */
    private static function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new self();
        }

        return $instance;
    }

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
            $prefix = substr(md5(__DIR__), 0, 5) . '.' . trim(str_replace('\\', '.', Application::$NAMESPACE), '.').'.';
        }

        return $prefix;
    } // function


    /**
     * Fetch a setting from cache. If not exists false is returned.
     *
     * @see apc_fetch
     *
     * @param string $name
     * @param int $timestamp invalidate cache, if cache is older then given timestamp
     *
     * @return mixed
     */
    public static function fetch($name, $timestamp = null)
    {
        // use APC cache if available and useful (not on CLI)
        if(function_exists('apc_fetch') && php_sapi_name() != 'cli') {
            $cache = apc_fetch(self::getPrefix() . $name);
        } else {
            $cache = self::getInstance()->getEntry(self::getPrefix() . $name);
        }

        // invalidate cache, if it is older than given timestamp
        if($timestamp && isset($cache['timestamp']) && $timestamp > $cache['timestamp']) {
            return false;
        }

        return isset($cache['data']) ? $cache['data'] : false;
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
        // store timestamp, since APC doesn't provide API to get age of an entry
        $entry = [ 'timestamp' => time(), 'data' => $value ];

        // use APC cache if available and useful (not on CLI)
        if(function_exists('apc_store') && php_sapi_name() != 'cli') {
            return apc_store(self::getPrefix() . $name, $entry, $ttl);
        } else {
            return self::getInstance()->setEntry(self::getPrefix() . $name, $entry);
        }
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
        // use APC cache if available and useful (not on CLI)
        if(function_exists('apc_delete') && php_sapi_name() != 'cli') {
            return (bool) apc_delete(self::getPrefix() . $name);
        } else {
            return self::getInstance()->deleteEntry(self::getPrefix() . $name);
        }
    } // function

    /**
     * @param $name cache key
     * @return bool|mixed cache value, or false if not set
     */
    private function getEntry($name)
    {
        if(!isset($this->data[$name])) {
            return false;
        }
        return $this->data[$name];
    }

    /**
     * @param $name cache key
     * @param $value cache value
     */
    private function setEntry($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param $name cache key
     * @param bool
     */
    private function deleteEntry($name)
    {
        if(!isset($this->data[$name])) {
            return false;
        }

        unset($this->data[$name]);
    }
}// class
