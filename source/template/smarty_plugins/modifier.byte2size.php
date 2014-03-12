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
 * Get bytes and return a string in a more readable fashion.
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author dangerground
 * @since 1.0
 *
 * @param int $bytes
 *
 * @return string
 */
function smarty_modifier_byte2size($bytes)
{
    $sizes = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
    $multiple_of = 1024;


    $count = count($sizes);
    for ($index = 0; $bytes >= $multiple_of && $index < $count; ++$index) {
        $bytes = $bytes / $multiple_of;
    }

    return rtrim(sprintf("%01.2f", $bytes), '0.').' '.$sizes[$index];
} // function
