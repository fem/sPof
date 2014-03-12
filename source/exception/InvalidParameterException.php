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

namespace FeM\sPof\exception;

/**
 * This class is thrown when invalid parameters are given. A list of parameters and their description can be retrieved
 * via getParameters().
 *
 * @api
 *
 * @package FeM\sPof\exception
 * @author dangerground
 * @since 1.0
 */
class InvalidParameterException extends \Exception
{
    /**
      * Consists of arrays with name, description.
      *
      * @var array
      */
    private $name = [];


    /**
      * Add a new parameter to the list of failing parameters.
      *
      * @param string  $name name of the failing parameter
      * @param string  $description description of the error
      */
    public function addParameter($name, $description = '')
    {
        $this->name[] = ['name' => $name, 'description' => $description];
    } // function addParameter


    /**
      * Returns a list of failed parameters.
      *
      * @return array
      */
    public function getParameters()
    {
        return $this->name;
    } // function getParameter


    /**
      * Returns a the number of failed parameters.
      *
      * @return int
      */
    public function getFailCount()
    {
        return count($this->name);
    } // function getParameter
}// class
