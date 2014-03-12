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

use FeM\sPof\Authorization;

/**
 * This view is suited for every view which should have minimal overhead.
 *
 * @package FeM\sPof\view
 * @author dangerground
 * @since 1.0
 */
abstract class AbstractRawView extends AbstractView
{
    /**
     * Current authorization context.
     *
     * @api
     *
     * @var \FeM\sPof\Authorization
     */
    protected $auth;


    /**
     * @internal
     */
    public function __construct()
    {
        $this->trackStandardSession();
        $this->auth = Authorization::getInstance();
    } // constructor


    /**
     * Handle exceptions thrown during initialization or execution of the show method.
     *
     * @param \Exception $exception
     *
     * @return void
     */
    public static function handleException(\Exception $exception)
    {
        if ($exception instanceof \FeM\sPof\exception\NotAuthorizedException) {
            self::sendForbidden();
        }

        self::sendInternalError();
    } // function


    /**
     * No special handling for raw views.
     */
    public function display()
    {
        exit;
    }
}// class
