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
 * Represents a Checkbox-element
 *
 * @package FeM\sPof\form\element
 * @author dangerground
 * @since 1.0
 */
class Checkbox extends \FeM\sPof\form\AbstractInputElement
{

    /**
     * Create new instance.
     *
     * @param string $field
     * @param string $value (optional)
     * @param bool $required (optional)
     * @param string $class (optional)
     */
    public function __construct($field, $value = "1", $required = false, $class = '')
    {
        parent::__construct('checkbox', $field, $required);
        $this->addAttribute('value', $value);
        if (!empty($class)) {
            $this->appendAttribute('class', $class);
        }
    } // constructor


    /**
     * Set to "checked" if value matches the predefined value.
     *
     * @param string $text
     */
    public function setValue($text)
    {
        if ($this->attributes['value'] == $text) {
            $this->addAttribute('checked', 'checked');
            $this->addAttribute('placeholder', $text);
        }
    } // function
}// class
