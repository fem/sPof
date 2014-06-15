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
 * Format a date for RSS feeds.
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author deka
 * @since 1.0
 *
 * @api
 *
 * @param string $string
 * @param string $default_date (optional)
 *
 * @return string
 */
function smarty_modifier_date_format_rfc822($string, $default_date = '')
{
    require_once SMARTY_PLUGINS_DIR.'shared.make_timestamp.php';

    if ($string != '') {
        $timestamp = smarty_make_timestamp($string);
    } elseif ($default_date != '') {
        $timestamp = smarty_make_timestamp($default_date);
    } else {
        return '';
    }

    $currentLocale = setlocale(LC_TIME, null);
    setlocale(LC_TIME, "C");
    $return = strftime('%a, %d %b %Y %H:%M:%S %z', $timestamp);
    setlocale(LC_TIME, $currentLocale);
    return $return;
} // function
