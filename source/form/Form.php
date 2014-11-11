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

use FeM\sPof\Renderable;
use FeM\sPof\template\HtmlTemplate;

/**
 * Represent a form, which consist of several FormRow elements.
 *
 * @package FeM\sPof\form
 * @author dangerground
 * @since 1.0
 */
class Form extends AbstractFormGroup implements Renderable
{

    /**
     * Keep a list of all fields for the whole form. References to the order of the elements is kept in $fieldset.
     *
     * @internal
     *
     * @var AbstractFormElement[]
     */
    private $field = [];

    /**
     * Group all fields/buttons by fieldset. There should be at least one field in each fieldset.
     *
     * @internal
     *
     * @var array
     */
    private $fieldset = [];

    /**
     * Keep a list of all fields for the whole form. References to the order of the elements is kept in $fieldset.
     *
     * @internal
     *
     * @var array
     */
    private $button = [];


    /**
     * This variable will keep the route name for the form.
     *
     * @internal
     *
     * @var string
     */
    private $route;

    /**
     * Context/Arguments for the route.
     *
     * @internal
     *
     * @var array
     */
    private $routeContext = [];


    /**
     * Reference to the current fieldset
     *
     * @internal
     *
     * @var int
     */
    private $set = 0;

    /**
     * Tell if the form was activated and can get used as an form.
     *
     * @internal
     *
     * @var bool
     */
    private $active = false;


    /**
     * Add new row and assign element to it.
     *
     * @api
     *
     * @param AbstractFormElement $element
     *
     * @return AbstractFormElement
     */
    public function &addElement(\FeM\sPof\form\AbstractFormElement &$element)
    {
        $row = $this->addRow();
        $row->addElement($element);

        return $element;
    } // function

    /**
     * Create a new row and return it.
     *
     * @api
     *
     * @param $class
     *
     * @return FormRow
     */
    final public function addRow($class = null)
    {
        $row = new FormRow($this, $class);
        $this->fieldset[$this->set]['rows'][] = &$row;
        return $row;
    } // function


    /**
     * Add a new field based element (input element) to the list.
     *
     * @internal
     *
     * @param AbstractFormElement $element
     * @param int $elementIndex index of the element in the current row
     */
    final public function addField(\FeM\sPof\form\AbstractFormElement &$element, $elementIndex)
    {
        $name = $element->getName();
        if (!empty($name)) {
            $this->field[$name] = [
                'set' => $this->set,
                'row' => count($this->fieldset[$this->set]['rows'])-1,
                'element' => $elementIndex
            ];
        }
    } // function


    /**
     * Returns if a form object is actively used or just there for another show method.
     *
     * @internal
     *
     * @return bool
     */
    final public function isActive()
    {
        return $this->active;
    } // function


    /**
     * Set to true if a form object is initialized. Set to false if form is not used.
     *
     * @internal
     *
     * @param bool $active
     */
    final public function setActive($active)
    {
        $this->active = $active;
    } // function


    /**
     * Add a new Fieldset, all following fields will be added the new fieldset. If this command is executed for the
     * first time, the name of the former anonymous named fieldset will apply the given name.
     *
     * @api
     *
     * @param string $title
     */
    final public function addFieldset($title)
    {
        if (!isset($this->fieldset[$this->set]['rows'])) {
            $this->fieldset[$this->set]['rows'] = [];
        }

        if (isset($this->fieldset[$this->set]['name'])) {
            $this->set++;
        }
        $this->fieldset[$this->set]['name'] = $title;
    } // function


    /**
     * Set the route for this form.
     *
     * @api
     *
     * @param string $route
     * @param array $routeContext (optional)
     */
    final public function setRoute($route, array $routeContext = [])
    {
        $this->route = $route;
        $this->routeContext = $routeContext;
    } // function

    /**
     * Set the route context only for this form.
     *
     * @api
     *
     * @param array $context
     */
    final public function setRouteContext(array $context)
    {
        $this->routeContext = $context;
    } // function


    /**
     * Set option values for a select field.
     *
     * @api
     *
     * @param string $field
     * @param array $options
     */
    final public function setOptions($field, array $options)
    {
        $element = &$this->getElement($field);
        if ($element instanceof element\Select) {
            $element->setOptions($options);
        }
    } // function


    /**
     * Add a row with date & time input elements.
     *
     * @api
     *
     * @param string $fieldPrefix
     * @param string $dateLabel
     * @param string $timeLabel
     * @param bool $required (optional)
     * @param string $rowClass (optional)
     */
    final public function addDateTimeRow($fieldPrefix, $dateLabel, $timeLabel, $required = true, $rowClass = null)
    {
        $row = $this->addRow($rowClass);
        $row->addDate($fieldPrefix.'Date', $dateLabel, $required);
        $row->addTime($fieldPrefix.'Time', $timeLabel, $required);
    } // function


    /**
     * Add a new button to the form.
     *
     * @api
     *
     * @param string $title
     * @param string $name (optional)
     * @param string $value (optional)
     */
    final public function addButton($title, $name = null, $value = null)
    {
        $this->fieldset[$this->set]['buttons'][] = [
            'label' => $title,
            'type' => 'submit',
            'name' => $name,
            'value' => $value,
        ];
    } // function


    /**
     * Get the current value of a field.
     *
     * @api
     *
     * @param string $field name
     * @param string $type (optional) type which is returned, e.g. bool, int, array
     *
     * @return mixed
     */
    final public function getValue($field, $type = null)
    {
        return $this->getElement($field)->getValue($type);
    } // function


    /**
     * Get all values of the form.
     *
     * @api
     *
     * @return mixed
     */
    final public function getValues()
    {
        $values = [];
        foreach ($this->field as $name => $field) {
            $values[$name] = $this->getElement($name)->getValue();
        }
        return $values;
    } // function


    /**
     * Set Default values for the given fields.
     *
     * @api
     *
     * @param array $context key value pair, with key = fieldname
     */
    final public function setDefaults(array $context)
    {
        foreach ($context as $field => $default) {
            $this->getElement($field)->setDefault($default);
        }
    } // function


    /**
     * Get an already assigned element-object from the form.
     *
     * @api
     *
     * @throws \InvalidArgumentException
     *
     * @param string $name fieldname
     *
     * @return AbstractFormElement&
     */
    private function &getElement($name)
    {
        if (!isset($this->field[$name])) {
            throw new \InvalidArgumentException(_s(
                'Konnte das Formularfeld "%s" nicht in den Einstellungen finden, aber es wurde versucht darauf '
                .'zuzugreifen.',
                $name
            ));
        }

        $field = $this->field[$name];
        $elements = $this->fieldset[$field['set']]['rows'][$field['row']]->getElements();
        $element = &$elements[$field['element']];
        return $element;
    } // function


    /**
     * Render the form and return as HTML-string.
     *
     * @api
     *
     * @return string
     */
    final public function render()
    {
        $template = HtmlTemplate::getInstance();
        $template->assign('route', $this->route);
        $template->assign('routeContext', $this->routeContext);
        $template->assign('fieldsets', $this->fieldset);
        $template->assign('buttons', $this->button);

        return $template->fetch('form/form.tpl');
    } // function render


    /**
     * Render the form.
     *
     * @see Form::render()
     */
    public function __toString()
    {
        return $this->render();
    } // function
}// class
