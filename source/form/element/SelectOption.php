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
 * Represents a option of a select-element.
 *
 * @internal
 *
 * @package FeM\sPof\form\element
 * @author dangerground
 * @since 1.0
 */
class SelectOption extends \FeM\sPof\form\AbstractFormElement
{

    /**
     * Referenced element.
     *
     * @internal
     *
     * @var string
     */
    public static $TAG = 'option';


    /**
     * Create new instance.
     *
     * @param string $value
     * @param string $key (optional)
     * @param bool $selected (optional)
     */
    public function __construct($value, $key = null, $selected = false)
    {
        if (is_array($value) && isset($value['attributes'])) {
            foreach ($value['attributes'] as $attribute_key => $attribute_value) {
                $this->addAttribute($attribute_key, $attribute_value);
            }
            $value = $value[''];
            $this->escapeInnerHtml = false;
        }

        $this->setValue($value);
        if ($key !== null) {
            $this->addAttribute('value', $key);
        }
        if ($selected) {
            $this->addAttribute('selected', 'selected');
        }
    } // function
}// class
