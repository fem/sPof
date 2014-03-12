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
 * Truncates a string with the HTML structure in mind, so it won't truncate inside of tags and all tags will be closed.
 * In the end there will be $length characters output with valid html formatting for them, that also means that the
 * real length will be greather than (or equal if no HTML) to the $length.
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author dangerground
 * @since 1.0
 *
 * @api
 *
 * @param string $string
 * @param int $length (optional)
 * @param string $etc (optional)
 * @param bool $break_words (optional)
 * @param bool $middle (optional)
 *
 * @return string
 */
function smarty_modifier_html_truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false)
{
    require_once SMARTY_PLUGINS_DIR.'modifier.truncate.php';

    if ($length == 0) {
        return '';
    }

    // strlen > $length
    if (!isset($string[$length])) {
        return $string;
    }

    // use original modifier when possible
    if (strpos($string, '<') === false) {
        return smarty_modifier_truncate($string, $length, $etc, $break_words, $middle);
    }

    // would it be short enough if we drop the tags from count?
    if (!isset(strip_tags($string)[$length])) {
        return $string;
    }

    // calculate the position to calculate the length and truncate using original function
    $parts = preg_split('#(<[^>]+>)#', $string, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_DELIM_CAPTURE);
    $reallength = 0;
    $taglength = 0;
    $lastpos = 0;
    foreach ($parts as $part) {
        $len = $part[1] - $lastpos;
        if (strpos($part[0], '<') === 0) {
            $reallength += $len;
        } else {
            $taglength += $len;
        }
        $lastpos = $part[1];

        if ($reallength >= $length) {
            $string = smarty_modifier_truncate($string, $length+$taglength, $etc, $break_words, $middle);
            break;
        }
    }

    // get all opening tags
    preg_match_all('#<([^/][^>]*)>#i', $string, $start_tags);
    $start_tags = $start_tags[1];

    // get all closing tags
    preg_match_all('#</([a-z]+)>#i', $string, $end_tags);
    $end_tags = $end_tags[1];

    // gather tags that need to be closed
    $need_close = [];
    foreach ($start_tags as $tag) {
        $pos = array_search($tag, $end_tags);
        if ($pos !== false) {
            unset($end_tags[$pos]);
        } else {
            $need_close[] = $tag;
        }
    } // foreach

    // close all remaining open tags in reverse order
    $need_close = array_reverse($need_close);
    foreach ($need_close as $tag) {
        $string .= '</'.$tag.'>';
    }

    return $string;
} // function
