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
 * Calculate current percentage.
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
function smarty_function_percent($params, &$smarty)
{
    $cur = (preg_match('/^[0-9]+$/', $params['current']))?$params['current'] : 0;
    $max = (preg_match('/^[0-9]+$/', $params['maximum']) && $params['maximum'] > 0) ? $params['maximum'] : 1;
    if ($cur > $max) {
        $cur = $max;
    }
    return ceil(100 * $cur / $max);
} // function
