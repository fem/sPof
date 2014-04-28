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
 * Represents the label for a field.
 *
 * @package FeM\sPof\form\element
 * @author dangerground
 * @since 1.0
 */
class Label extends \FeM\sPof\form\AbstractFormElement
{
    /**
     * Referenced tag.
     *
     * @internal
     *
     * @var string
     */
    protected static $TAG = 'label';


    /**
     * Create new instance.
     *
     * @param string $name
     * @param string $title
     * @param bool $required (optional)
     * @param null $class (optional)
     */
    public function __construct($name, $title, $required = false, $class = null)
    {
        $this->innerHtml = $title;
        if ($required) {
            $this->innerHtml .= (new Plain('*'))->render();
        }
        $this->escapeInnerHtml = false;
        $this->addAttribute('class', $class);
        $this->addAttribute('for', str_replace('[]', '', $name));
    } // constructor
}// class
