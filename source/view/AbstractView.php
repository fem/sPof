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

use FeM\sPof\AbstractModule;
use FeM\sPof\Router;

/**
 * Parent class for all viewable content (something that is passed directly to the user). It's wise to inherit directly
 * from AbstractHtmlView oder AbstractRawView instead of this class.
 *
 * @package FeM\sPof\view
 * @author dangerground
 * @since 1.0
 */
abstract class AbstractView extends AbstractModule
{

    /**
     * By default use a wrapper to call
     *
     * @internal
     */
    public function __construct()
    {
        $this->executeShow();
    } // function


    /**
     * Wrapper for calling the self::$show-method. This wrapper is necessary to call
     *
     * @internal
     *
     * @param string $show
     * @throws \FeM\sPof\exception\NotImplementedException
     * @return mixed content
     */
    protected function executeShow($show = null)
    {
        // call show method
        if ($show === null) {
            $show = Router::getShow($show);
        }
        if (!method_exists(get_called_class(), $show)) {
            throw new \FeM\sPof\exception\NotImplementedException(
                'Could not find the show method. "'.get_called_class().'::'.$show.'"'
            );
        }
        return $this->$show();
    } // function


    /**
     * What to do in case no view was be created.
     *
     * @api
     *
     * @return AbstractView|void
     */
    public static function handleNoViewFound()
    {
            static::sendNotFound();
    } // function


    /**
     * In case no other of the exceptions was thrown, the last resort will be to catch everything.
     *
     * @api
     *
     * @param \Exception $exception
     *
     * @return AbstractView|void
     */
    public static function handleException(\Exception $exception)
    {
        echo $exception->getMessage();
        static::sendInternalError();
    } // function


    /**
     * This method is called finally in execution order. By default there will be an error, inheriting views should
     * overwrite this.
     *
     * @api
     */
    public function display()
    {
        static::sendInternalError();
    } // function


    /**
     * Use every $_GET parameter as reference parameter to log the session
     *
     * @internal
     */
    final protected function trackStandardSession()
    {
        $parameters = $_GET;
        $reference = [];
        unset($parameters['module']);
        foreach ($parameters as $name => $param) {
            if (substr($name, -3) === '_id') {
                $reference[$name] = $param;
                unset($parameters[$name]);
            }
        }
        $this->trackSession($reference, $parameters);
    } // function


    /**
     * Log the session. Module and session id are already there
     *
     * @internal
     *
     * @param array $reference other reference parameters, e.g. new generated ids
     * @param array $parameters url parameters, e.g. from $_GET
     */
    final protected function trackSession(array $reference, array $parameters)
    {
        \FeM\sPof\model\LogSession::add([
            'session_id' => session_id(),
            'view' => Router::getModule(),
            'reference_parameters' => serialize($reference),
            'other_parameters' => serialize($parameters)
        ]);
    } // function
}// class
