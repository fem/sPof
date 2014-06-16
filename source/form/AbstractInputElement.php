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

namespace FeM\sPof\form;

/**
 * Represent all input-HTML-elements.
 *
 * @package FeM\sPof\form
 * @author dangerground
 * @since 1.0
 */
abstract class AbstractInputElement extends AbstractStandaloneFormElement
{

    /**
     * HTML-Tag name.
     *
     * @internal
     *
     * @var string
     */
    public static $TAG = 'input';


    /**
     * create new instance.
     *
     * @param string $type
     * @param string $field
     * @param bool $required
     */
    public function __construct($type, $field, $required = true)
    {
        $this->addAttribute('type', $type);
        $this->addAttribute('class', $type);
        $this->addAttribute('name', $field);
        if ($required) {
            $this->addAttribute('required', 'required');
        }
    } // constructor


    /**
     * Set the value attribute.
     *
     * @api
     *
     * @param string $text
     */
    public function setValue($text)
    {
        $this->addAttribute('value', $text);
    } // function
}// class
