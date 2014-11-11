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
 * Function alias to sprintf for easier gettext integration
 *
 * @api
 *
 * @param string $string text to translate
 * @param $vargs
 *
 * @return string translated text
 */
function __() {
    return call_user_func_array("sprintf", func_get_args());
}


/**
 * Function alias to sprintf for easier gettext integration. Specific to spof domain.
 *
 * @internal
 *
 * @param string $string text to translate
 * @param $vargs
 *
 * @return string translated text
 */
function _s() {
    $args = func_get_args();
    $args[0] = dgettext('spof', $args[0]);
    return call_user_func_array('sprintf', $args);
}
