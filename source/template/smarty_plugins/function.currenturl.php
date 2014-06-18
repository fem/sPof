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

/**
 * Create a new route based on the currently visited site, by disassembling the
 * url. Throwing all optional params away (these with 'name:value') and append
 * the params as new optional params.
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author dangerground
 * @author pegro
 *
 * @api
 *
 * @param array $params
 * @param Smarty $smarty (reference)
 *
 * @return string
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function smarty_function_currenturl($params, &$smarty)
{
    $rawUrl = false;

    $server = FeM\sPof\Config::get('server');

    // get pattern route
    if (isset($_SERVER['REQUEST_URI'])) {
        if ($server['path'] === '/') {
            $route = $_SERVER['REQUEST_URI'];
        } else {
            $route = str_replace($server['path'], '', $_SERVER['REQUEST_URI']);
        }

        if (strpos($_SERVER['REQUEST_URI'], '?') > 0) {
            $rawUrl = true;
        }

        // drop optional params
        foreach ($params as $key => $param) {
            if ($param === null) {
                continue;
            }
            $route = preg_replace('/'.($rawUrl ? ('&'.$key.'=') : ('\/?\/?[a-z0-9_]+:')).'[a-z0-9_]+/i', '', $route);
        }
    }

    // make default route based on basedir
    if (empty($route)) {
        $route = '/';
    }

    // get a list of valid new optional params
    $new = [$route];
    foreach ($params as $key => $param) {
        if ($param === null) {
            continue;
        }
        $new[] = $key.($rawUrl ? '=' : ':').$param;
    }

    // return route including new optional params
    return '//'.$_SERVER['SERVER_NAME'].$server['path'].ltrim(implode(($rawUrl ? '&' : '/'), $new), '/');
} // function
