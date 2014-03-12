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
 * Escape the string using base64 with the little addition, that a dot is used instead of the slash, makes it usable
 * for urls.
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author dangerground
 * @since 1.0
 *
 * @api
 *
 * @param string $text
 *
 * @return string
 */
function smarty_modifier_pbase64_encode($text)
{
    return \FeM\sPof\StringUtil::pbase64Encode($text);
} // function
