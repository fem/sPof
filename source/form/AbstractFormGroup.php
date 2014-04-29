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
 * Collection of methods which should be available for Form and FormRow classes.
 *
 * @package FeM\sPof\form
 * @author dangerground
 * @since 1.0
 */
abstract class AbstractFormGroup
{

    /**
     * Add a new element.
     *
     * @internal
     *
     * @param AbstractFormElement $element
     *
     * @return AbstractFormElement
     */
    abstract public function addElement(\FeM\sPof\form\AbstractFormElement &$element);


    /**
     * Add a new row. Or return the existing.
     *
     * @internal
     *
     * @param string $class
     *
     * @return FormRow
     */
    abstract public function addRow($class = null);


    /**
     * Add a label and a element to a new row. Label is inserted before the element.
     *
     * @api
     *
     * @param $label
     * @param AbstractFormElement $element
     *
     * @return AbstractFormElement
     */
    public function &addLabeled($label, \FeM\sPof\form\AbstractFormElement &$element)
    {
        $row = $this->addRow();
        $row->addElement(new element\Label($element->getName(), $label, $element->isRequired()));
        $row->addElement($element);

        return $element;
    } // function


    /**
     * Add a label and a time input to a new row. Label is inserted before the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param bool $required (optional)
     */
    final public function addTime($field, $label, $required = true)
    {
        $this->addLabeled($label, new element\TimeInput($field, $required))
             ->setDefault(date('H:i'));
    } // function


    /**
     * Add a label and a date input to a new row. Label is inserted before the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param bool $required (optional)
     */
    final public function addDate($field, $label, $required = true)
    {
        $this->addLabeled($label, new element\DateInput($field, $required))
             ->setDefault(date('d.m.Y'));
    } // function


    /**
     * Add a label and a one-line text input to a new row. Label is inserted before the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param int $maxLength (optional)
     * @param bool $required (optional)
     */
    final public function addText($field, $label, $required = true, $maxLength = 255)
    {
        $this->addLabeled($label, new element\TextInput($field, $required, $maxLength, 'long'));
    } // function


    /**
     * Add a label and a short one-line text input to a new row. Label is inserted before the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param int $maxLength (optional)
     * @param bool $required (optional)
     */
    final public function addShortText($field, $label, $required = true, $maxLength = 255)
    {
        $this->addLabeled($label, new element\TextInput($field, $required, $maxLength));
    } // function


    /**
     * Add a label and a url input to a new row. Label is inserted before the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param int $maxLength (optional)
     * @param bool $required (optional)
     */
    final public function addUrl($field, $label, $required = true, $maxLength = 255)
    {
        $this->addLabeled($label, new element\UrlInput($field, $required, $maxLength));
    } // function


    /**
     * Add a label, a checkbox input and a plaintext to a new row. Label is inserted before the input and plaintext is
     * last.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param string $description (optional)
     * @param string $value (optional)
     */
    final public function addCheckbox($field, $label, $description = null, $value = "1")
    {
        $row = $this->addRow();
        $row->addElement(new element\Label($field, $label));
        $row->addElement(new element\Checkbox($field, $value));
        $row->addElement(new element\Plain($description));
    } // function


    /**
     * Add a label and a checkbox input to a new row. Label is inserted after the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param string $value (optional)
     */
    final public function addSimpleCheckbox($field, $label, $value = "1")
    {
        $row = $this->addRow();
        $row->addElement(new element\Checkbox($field, $value, false));
        $row->addElement(new element\Label($field, $label, false, 'normal'));
    } // function


    /**
     * Add a label and a multi-line text input to a new row. Label is inserted before the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param bool $required (optional)
     * @param int $maxLength (optional)
     */
    final public function addTextarea($field, $label, $required = true, $maxLength = 65535)
    {
        $row = $this->addRow();
        $row->addElement(new element\Label($field, $label, $required, 'absLabel'));
        $row->addElement(new element\TextArea($field, $required, $maxLength));
    } // function


    /**
     * Add a label and a multi-line text input with Wysiwyg control to a new row. Label is inserted before the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param bool $required (optional)
     * @param int $maxLength (optional)
     */
    final public function addWysiwygArea($field, $label, $required = true, $maxLength = 65535)
    {
        $row = $this->addRow();
        $row->addElement(new element\Label($field, $label, $required, 'absLabel'));
        $row->addElement(new element\WysiwygArea($field, $required, $maxLength));
    } // function


    /**
     * Add a label and a file input to a new row. Label is inserted before the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param bool $required (optional)
     */
    final public function addFile($field, $label, $required = true)
    {
        $this->addLabeled($label, new element\FileInput($field, $required));
    } // function


    /**
     * Add a plaintext element to the row.
     *
     * @api
     *
     * @param string $text
     * @param bool $asLabel (optional)
     */
    final public function addPlain($text, $asLabel = false)
    {
        $this->addElement(new element\Plain($text, ($asLabel ? 'asLabel' : null)));
    } // function


    /**
     * Add a label and a select input to a new row. Label is inserted before the input.
     *
     * @api
     *
     * @param string $field
     * @param string $label
     * @param bool $required (optional)
     * @param array $options (optional)
     */
    final public function addSelect($field, $label, $required = true, array $options = [])
    {
        $select = new element\Select($field, $required);
        $this->addLabeled($label, $select)->setOptions($options);
        return $select;
    } // function
}// class
