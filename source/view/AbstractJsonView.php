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

/**
 * This view is suited for every view which returns JSON formatted content. Advantage of these operations is the
 * reduced overhead.
 *
 * @package FeM\sPof\view
 * @author dangerground
 * @since 1.0
 */
abstract class AbstractJsonView extends AbstractView
{
    /**
     * JSON resultset, put everything in here which and it will be returned.
     *
     * @api
     *
     * @var mixed
     */
    protected $resultSet = null;



    /**
     * Wrapper for calling the self::$show-method. This wrapper is necessary to call
     *
     * @internal
     *
     * @param string $show
     * @throws \FeM\sPof\exception\NotImplementedException
     * @return mixed content
     */
    public function executeShow($show = null)
    {
        // call show met
        if ($show === null) {
            $show = \FeM\sPof\Router::getShow($show);
        }
        if (!method_exists(get_called_class(), $show)) {
            throw new \FeM\sPof\exception\NotImplementedException(__(
                'Could not find the show method. "%s::%s"',
                get_called_class(),
                $show
            ));
        }
        return $this->$show();
    } // function


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
            static::sendForbidden();
        } elseif ($exception instanceof \FeM\sPof\exception\UnsupportedRequestMethod) {
            static::sendMethodNotAllowed($exception->getAllowed());
        } elseif ($exception instanceof \FeM\sPof\exception\BadRequestException) {
            header('Content-type: application/json');
            echo json_encode(['error' => $exception->getMessage()]);
            static::sendBadRequest();
        }

        header('Content-type: application/json');
        echo json_encode(['msg' => $exception->getMessage(), 'exception' => $exception]);
        static::sendInternalError();
    } // function


    /**
     * Return the JSON-Formatted content back to the requester.
     *
     * @api
     */
    public function display()
    {
        header('Content-type: application/json');
        if ($this->resultSet !== null) {
            echo json_encode($this->resultSet);
        }
        ob_end_flush();
        flush();
        exit;
    } // function
}// class
