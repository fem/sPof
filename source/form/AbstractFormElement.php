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
use FeM\sPof\Request;

/**
 * Represent a HTML-Element.
 *
 * @package FeM\sPof\form
 * @author dangerground
 * @since 1.0
 */
abstract class AbstractFormElement implements Renderable
{

    /**
     * Name of the HTML Element which is represented by this class.
     *
     * @internal
     *
     * @var string
     */
    protected static $TAG = null;

    /**
     * If true, there will be no closing tag/inner HTML. If false there will be inner HTML and a closing tag.
     *
     * @internal
     *
     * @var bool
     */
    protected static $STANDALONE = false;

    /**
     * Possible inner HTML, which will get rendered. If standalone is true, nothing will happen with this content. If
     * no standalone tag is chosen the content will be rendered if it is of type Renderable, otherwise the string will
     * be escaped and otherwise kept as it is.
     *
     * @internal
     *
     * @var string|Renderable
     */
    protected $innerHtml = null;

    /**
     * To escape inner html or not to escape.
     *
     * @internal
     *
     * @var bool
     */
    protected $escapeInnerHtml = true;

    /**
     * Represent the HTML tag attributes as key-value pair, where keys represent the attribute name and the value the
     * associated value, for the given attribute.
     *
     * @internal
     *
     * @var array
     */
    protected $attributes = [];


    /**
     * Default value, which is used if nothing was submitted via html.
     *
     * @internal
     *
     * @var mixed
     */
    protected $default;

    /**
     * Add a new tag attribute.
     *
     * @api
     *
     * @param $attribute
     * @param $value
     */
    protected function addAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;

        // special case
        if ($attribute === 'name' && !isset($this->attributes['id'])) {
            $this->attributes['id'] = preg_replace('/\[[^\]]*\]/', '', $value);
        }
    } // function


    /**
     * Append a value (with a space) to an existing value for a tag attribute.
     *
     * @api
     *
     * @param $attribute
     * @param $value
     */
    protected function appendAttribute($attribute, $value)
    {
        $this->attributes[$attribute] .= ' '.$value;
    } // function


    /**
     * Get the name of the HTML-Element.
     *
     * @api
     *
     * @return string
     */
    public function getName()
    {
        if (isset($this->attributes['name'])) {
            return $this->attributes['name'];
        }

        return '';
    } // function


    /**
     * Get current value of the element from the request, if there is no request, the default value will be returned.
     *
     * @api
     *
     * @throws \InvalidArgumentException if the attribute name is missing (and the element is no "field").
     *
     * @return mixed
     */
    public function getValue()
    {
        if (!isset($this->attributes['name'])) {
            throw new \InvalidArgumentException('Could not find a named form element: '.var_export($this, true));
        }

        // by default everything is a string
        $method = 'getStrParam';

        // if set, use another type
        if (isset($this->attributes['type'])) {
            switch ($this->attributes['type']) {

                case 'array':
                    $method = 'getArrayParam';
                    break;

                case 'file':
                    $method = 'getFileParam';
                    break;

                case 'int':
                    $method = 'getIntParam';
                    break;

                case 'bool':
                    $method = 'getBoolParam';
                    break;
            }
        }

        // try with default value
        if (isset($this->default)) {
            return Request::$method($this->attributes['name'], $this->default);
        }

        return Request::$method($this->attributes['name']);
    } // function


    /**
     * Return if this element is required.
     *
     * @api
     *
     * @return bool
     */
    public function isRequired()
    {
        if (isset($this->attributes['required'])) {
            return $this->attributes['required'];
        }

        return false;
    } // function


    /**
     * Overwrite the innerHtml
     *
     * @api
     *
     * @param $text
     */
    public function setValue($text)
    {
        $this->innerHtml = $text;
    } // function


    /**
     * Overwrite the default value
     *
     * @api
     *
     * @param mixed $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    } // function


    /**
     * Prepare everything for rendering, so that you'll later just need to start the rendering. This method assigned
     * everything which is normally required, to the template.
     *
     * @api
     */
    public function renderPrepare()
    {

        // TODO testing only
        unset($this->attributes['required']);

        if (!empty($this->attributes['name'])) {
            $this->setValue($this->getValue());
        }

        $template = HtmlTemplate::getInstance();
        $template->assign('tag', static::$TAG);
        $template->assign('standalone', static::$STANDALONE);
        $template->assign('attributes', $this->attributes);
        $template->assign('escapeInnerHtml', $this->escapeInnerHtml);
        $template->assign('innerHTML', $this->innerHtml);

    } // function


    /**
     * Render this element.
     *
     * @api
     *
     * @return string
     */
    public function render()
    {
        try {
            $this->renderPrepare();
            return HtmlTemplate::getInstance()->fetch('form/html_tag.tpl');
        } catch (\Exception $e) {
            //Logger::getInstance()->exception($e);
            return '';
        }
    } // function
}// class
