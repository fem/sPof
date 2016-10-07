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

namespace FeM\sPof;

/**
 * Provide general HTTP-Methods for views and controllers.
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
abstract class AbstractModule
{
    /**
     * HTTP Response codes
     */
    const HTTP_CODE_OK = 200;
    const HTTP_CODE_CREATED = 201;

    const HTTP_CODE_MOVED_TEMPORARILY = 302;
    const HTTP_CODE_NOT_MODIFIED = 304;

    const HTTP_CODE_BAD_REQUEST = 400;
    const HTTP_CODE_UNAUTHORIZED = 401;
    const HTTP_CODE_FORBIDDEN = 403;
    const HTTP_CODE_NOT_FOUND = 404;
    const HTTP_CODE_METHOD_NOT_ALLOWED = 405;
    const HTTP_CODE_UNPROCESSABLE = 422;

    const HTTP_CODE_INTERNAL_ERROR = 500;
    const HTTP_CODE_NOT_IMPLEMENTED = 501;

    /**
     * Answers the current request with a HTTP Satus Code 201: "Created". The request is then redirected to the given
     * route and further execution of the script is stopped
     *
     * @api
     *
     * @param string $route
     * @param array $routeContext (optional)
     */
    public static function sendCreated($route, array $routeContext = [])
    {
        http_response_code(self::HTTP_CODE_CREATED);
        Router::redirect($route, $routeContext);
        exit;
    } // function


    /**
     * Answers the current request with a HTTP Status Code 400: "Bad Request" and stops further execution.
     *
     * @api
     */
    public static function sendBadRequest()
    {
        http_response_code(self::HTTP_CODE_BAD_REQUEST);
        exit;
    } // function


    /**
     * Answers the current request with a HTTP Status Code 403: "Forbidden" and stops further execution. This method
     * should be used, when a user is lacking of permission to handle a specific operation.
     *
     * @api
     */
    public static function sendForbidden()
    {
        http_response_code(self::HTTP_CODE_FORBIDDEN);
        exit;
    } // function


    /**
     * Answers the current request with a HTTP Status Code 404: "Not Found" and stops further execution.
     *
     * @api
     */
    public static function sendNotFound()
    {
        http_response_code(self::HTTP_CODE_NOT_FOUND);
        exit;
    } // function


    /**
     * Answers the current request with a HTTP Status Code 405: "Method Not Allowed" and stops further execution.
     *
     * @api
     *
     * @param array $allowed allowed HTTP Methods
     */
    public static function sendMethodNotAllowed(array $allowed = [])
    {
        header('Allow: '.implode(', ', $allowed));
        http_response_code(self::HTTP_CODE_METHOD_NOT_ALLOWED);
        exit;
    } // function


    /**
     * Answers the current request with a HTTP Status Code 500: "Internal Server Error" and stops further execution.
     *
     * @api
     */
    public static function sendInternalError()
    {
        ob_flush();
        //flush();
        http_response_code(self::HTTP_CODE_INTERNAL_ERROR);
        exit;
    } // function
}// class
