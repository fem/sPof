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
 * Represent a form row, which may consist of several elements.
 *
 * @package FeM\sPof\form
 * @author dangerground
 * @since 1.0
 */
class FormRow extends AbstractFormGroup
{

    /**
     * List of all elements from this row.
     *
     * @internal
     *
     * @var AbstractFormElement[]
     */
    protected $elements = [];

    /**
     * Reference to the parent (the form).
     *
     * @internal
     *
     * @var Form
     */
    protected $form = null;

    /**
     * CSS class of the current form.
     *
     * @internal
     *
     * @var string
     */
    protected $class = null;


    /**
     * Create a new row.
     *
     * @internal
     *
     * @param Form $form
     * @param string $class (optional)
     */
    public function __construct($form, $class = null)
    {
        $this->form = &$form;
        $this->class = $class;
    } // constructor


    /**
     * Add a new Element to the row.
     *
     * @api
     *
     * @param AbstractFormElement $element
     *
     * @return AbstractFormElement
     */
    public function &addElement(\FeM\sPof\form\AbstractFormElement $element)
    {
        $this->elements[] = $element;
        $this->form->addField($element, count($this->elements)-1);

        return $element;
    } // function


    /**
     * Wrapper class so that we don't have to differ between Form and FormRow handling from user side.
     *
     * @internal
     *
     * @param string $class
     *
     * @return FormRow
     */
    public function addRow($class = null)
    {
        if ($class !== null) {
            $this->class = $class;
        }

        return $this;
    } // function


    /**
     * Get all elements of a row.
     *
     * @api
     *
     * @return AbstractFormElement[]
     */
    public function getElements()
    {
        return $this->elements;
    } // function


    /**
     * Get CSS Class of this row.
     *
     * @api
     *
     * @return AbstractFormElement[]
     */
    public function getClass()
    {
        return $this->class;
    } // function
}// class
