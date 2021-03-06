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

namespace FeM\sPof\form\element;

/**
 * Do not Use, internal usage only.
 *
 * @internal
 *
 * @package FeM\sPof\form\element
 * @author dangerground
 * @since 1.0
 */
class TextAreaInternal extends \FeM\sPof\form\AbstractFormElement
{

    /**
     * Referenced HTML-element.
     *
     * @internal
     *
     * @var string
     */
    public static $TAG = 'textarea';


    /**
     * Create new instance.
     *
     * @param string $field
     * @param bool $required (optional)
     * @param int $maxlength (optional)
     * @param string $class (optional)
     */
    public function __construct($field, $required = true, $maxlength = 65535, $class = '')
    {
        $this->addAttribute('name', $field);
        if ($required) {
            $this->addAttribute('required', $required);
        }
        $this->addAttribute('maxlength', $maxlength);
        $this->addAttribute('class', 'textarea');
        if (!empty($class)) {
            $this->appendAttribute('class', $class);
        }
    } // constructor
}// class
