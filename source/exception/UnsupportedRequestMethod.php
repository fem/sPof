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
 * This exception is thrown in case of an invalid request type for the current request.
 *
 * @api
 *
 * @package FeM\sPof\exception
 * @author dangerground
 * @since 1.0
 */
class UnsupportedRequestMethod extends \Exception
{

    /**
     * List of allowed request method.
     *
     * @var array
     */
    private $allowed = [];


    /**
     * Create new instance.
     *
     * @param string $message
     * @param array $allowed Allowed request methods
     */
    public function __construct($message, array $allowed = [])
    {
        parent::__construct($message);

        $this->allowed = $allowed;
    } // function


    /**
     * Return the allowed request methods.
     *
     * @return array
     */
    public function getAllowed()
    {
        return $this->allowed;
    } // function
}// class