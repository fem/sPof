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
 * Use markup for text.
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author dangerground
 * @since 1.0
 *
 * @api
 *
 * @param string $string
 * @param bool $tohtml (optional)
 * @param bool $convertmarkup (optional)
 * @param bool $nl2br (optional)
 * @param bool $marklinks (optional)
 * @param int $hstart (optional) headline to start with
 *
 * @return string
 */
function smarty_modifier_markup2html($string, $tohtml = true, $convertmarkup = true, $nl2br = true, $marklinks = true, $hstart = 4)
{
    $out = $string;

    // split text for non-markup texts
    $codes = preg_split('#(%%|</?code>)#mi', $out, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $usemarkup = true;
    foreach ($codes as &$code) {

        // handle nowiki text
        if ($code === '%%') {
            if ($usemarkup) {
                $code = '';
                $usemarkup = false;
                continue;
            } else {
                $code = '';
                $usemarkup = true;
                continue;
            }
        } elseif ($code === '<code>') {

            // do not apply markup formatting
            $code = '<pre><code>';
            $usemarkup = false;
            continue;
        } elseif ($code === '</code>') {

            // start to apply markup again
            $code = '</code></pre>';
            $usemarkup = true;
            continue;
        }

        // format text
        if ($usemarkup) {
            $code = \FeM\sPof\StringUtil::markup2html($code, $tohtml, $convertmarkup, $nl2br, $marklinks, $hstart);
        } else {
            $code = \FeM\sPof\StringUtil::markup2html($code, true, false, false);
        }
    }

    return implode('', $codes);
} // function
