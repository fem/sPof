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
 * Represents a single-select-input-element.
 *
 * @package FeM\sPof\form\element
 * @author dangerground
 * @since 1.0
 */
class Select extends \FeM\sPof\form\AbstractFormElement
{

    /**
     * Referenced element.
     *
     * @internal
     *
     * @var string
     */
    public static $TAG = 'select';

    /**
     * Selectable options.
     *
     * @internal
     *
     * @var array
     */
    protected $options = [];

    /**
     * Current selected value
     *
     * @internal
     *
     * @var string
     */
    protected $selectedValue = null;


    /**
     * Create new instance.
     *
     * @param string $field
     * @param bool $required (optional)
     * @param array $options (optional)
     */
    public function __construct($field, $required = true, $options = [])
    {
        $this->addAttribute('name', $field);
        if ($required) {
            $this->addAttribute('required', $required);
        }
        if (!empty($options)) {
            $this->setOptions($options);
        }
        $this->escapeInnerHtml = false;
    } // constructor


    /**
     * Set available options.
     *
     * @api
     *
     * @param array $options key-value pair
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    } // function


    /**
     * Set the selected value.
     *
     * @api
     *
     * @param $value
     */
    public function setValue($value)
    {
        $this->selectedValue = $value;
    } // function


    /**
     * Render the element.
     *
     * @api
     *
     * @return string
     */
    public function render()
    {
        parent::renderPrepare();
        foreach ($this->options as $key => $value) {
            $this->innerHtml .= (new SelectOption($value, $key, $key == $this->selectedValue))->render();
        }
        return parent::render();
    } // function
}// class
