<?php
/**
 * This file is part of sPof.
 *
 * FIXME license
 *
 * @copyright 2003-2014 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
 * @link      http://spof.fem-net.de
 */

namespace FeM\sPof\view;

use FeM\sPof\model\User;

/**
 * Implement HTTP Basic Auth.
 *
 * @package FeM\sPof
 * @author dangerground
 * @author thillux
 * @since 1.0
 */
trait ApiAuthenticationTrait
{
    /**
     * Uses the user credentials sent with the HTTP-Header to authenticate the user calling the API.
     *
     * œ@api
     *
     * @throws \FeM\sPof\exception\BasicAuthException
     *
     * @return boolean True, if user with the given password was authenticated successfully, else false.
     */
    final public function authenticate()
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
            throw new \FeM\sPof\exception\BasicAuthException("Missing authentication credentials");
        }

        $user_id = User::getIdByCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        if ($user_id === false) {
            throw new \FeM\sPof\exception\BasicAuthException("Wrong user and/or password.");
        }

        return $user_id;
    } // function


    /**
     * Sets the appropriate HTTP return code used to indicate that invalid user credential for API authentication
     * were given. Further execution is stopped. This method should be used, when no credentials for the API where used.
     *
     * @api
     */
    public static function sendUnauthorized()
    {
        header('WWW-Authenticate: Basic realm="sPi API"');
        http_response_code(401);
        exit;
    } // function


    /**
     * Handle exceptions thrown during usage of the parent class.
     *
     * @internal
     *
     * @param \Exception $exception
     */
    public static function handleException(\Exception $exception)
    {
        if ($exception instanceof \FeM\sPof\exception\BasicAuthException) {
            static::sendUnauthorized();
        }

        parent::handleException($exception);
    } // function
}// trait
