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

namespace FeM\sPof\view;

use FeM\sPof\Application;
use FeM\sPof\Router;
use FeM\sPof\Request;

/**
 * Tell the class which uses this trait, that there might be a form behind a show method, which in combination with
 * AbstractHtmlView will be automatically used & rendered.
 *
 * @package FeM\sPof\view
 * @author dangerground
 * @since 1.0
 */
trait FormViewTrait
{
    /**
     * Reference to the current form.
     *
     * @api
     *
     * @var \FeM\sPof\form\Form
     */
    protected $form;


    /**
     * Initialize the form property.
     *
     * @api
     */
    protected function initializeForm($namespace = null)
    {
        var_dump($namespace);
        if ($namespace !== null) {
            $classname = $namespace.'\\';
        } else {
            $classname = Application::$NAMESPACE.'form\\';
        }
        var_dump($classname);

        $classname .= Router::getModule().'Form';
        $show = Request::getStrParam('show');
        $this->form = new $classname();

        if (method_exists($classname, $show)) {
            $this->form->$show();
            $this->form->setActive(true);
        }
    } // function
}// trait
