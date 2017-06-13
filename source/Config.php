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

use Symfony\Component\Yaml\Yaml;

/**
 * Class to read config.
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
class Config
{

    /**
     * The whole config store.
     *
     * @internal
     *
     * @var array
     */
    private $config = [];


    /**
     * Get the instance.
     *
     * @api
     *
     * @return Config
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
     * Create a new instance
     *
     * @internal
     */
    private function __construct()
    {
        $dir = Application::$FILE_ROOT.'config/';

        // files to check
        $files = [
            $dir.'default.yml',
            $dir.'local.yml'
        ];

        // check file existance and age
        $file_lastchange = 0;
        foreach ($files as $idx => $file) {
            // remove non-existant files from list
            if(!file_exists($file)) {
                unset($files[$idx]);
                continue;
            }
            $file_lastchange = max(filemtime($file), $file_lastchange);
        }

        // check cache and return, if nothing has changed
        $cache = Cache::fetch('config_parsed', $file_lastchange);
        if($cache) {
            $this->config = $cache;
            return $this->config;
        }

        // parse files
        foreach ($files as $file) {
            $this->config = array_replace_recursive($this->config, Yaml::parse(file_get_contents($file)));
        }

        Cache::store('config_parsed', $this->config);
    } // function


    /**
     * Get config setting. If option does not exist, return the default setting.
     *
     * @api
     *
     * @param string $name
     * @param mixed $default (optional)
     *
     * @return mixed
     */
    public static function get($name, $default = false)
    {
        $me = self::getInstance();

        if (!isset($me->config[$name])) {
            //Logger::getInstance()->warning('Config option does not exist: '.$name);
            return $default;
        }

        if (is_array($default) && is_array($me->config[$name])) {
            return array_merge($default, $me->config[$name]);
        }

        // non array, so pass value directly
        return $me->config[$name];
    } // function


    /**
     * Get a sub-config setting.
     *
     * @api
     *
     * @param string $name
     * @param string $detail
     *
     * @return mixed
     */
    public static function getDetail($name, $detail, $default = false)
    {
        $me = self::getInstance();

        if (isset($me->config[$name][$detail])) {
            //Logger::getInstance()->warning('Config option does not exist: '.$name);
            return $me->config[$name][$detail];
        }

        if (!is_array($default)) {
            return $default;
        }

        if (isset($default[$detail])) {
            return $default[$detail];
        }

        return false;
    } // function


    /**
     * Get the whole config.
     *
     * @internal
     *
     * @return mixed
     */
    public static function getAll()
    {
        // non array, so pass value directly
        return self::getInstance()->config;
    } // function
}// class
