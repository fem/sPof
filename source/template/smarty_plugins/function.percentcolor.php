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
 * Calculate a color based on current percentage of max.
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author deka
 * @author pegro
 * @since 1.0
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
function smarty_function_percentcolor($params, &$smarty)
{
    $cur = (isset($params['current']) && preg_match('/^[0-9]+$/', $params['current']))?$params['current']:0;
    $min = (isset($params['minimum']) && preg_match('/^[0-9]+$/', $params['minimum']))?$params['minimum'] + 0:0;
    $max = (isset($params['maximum']) && preg_match('/^[0-9]+$/', $params['maximum']) && $params['maximum'] > 0)
        ? $params['maximum']
        : 1;
    if ($cur > $max) {
        $cur = $max;
    }
    if ($cur < $min) {
        $cur = $min;
    }
    $value = dechex(round(strval(255 - 238 * ($cur - $min) / ($max - $min))));

    if (!isset($params['color'])) {
        $params['color'] = '';
    }
    switch ($params['color']) {
        case 'green':
            return "33ee".$value;

        case 'blue':
            return "33".$value."ff";

        case 'red':
        default:
            return "ff".$value."00";
    }
} // function
