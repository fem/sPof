<?php
/**
 * This file is part of sPof.
 *
 * FIXME license
 *
 * @copyright 2003-2014 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
 * @link      http://spof.fem-net.de
 */

namespace FeM\sPof;

/**
 * Collection of HTTP-request related functions.
 *
 * @package FeM\sPof
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
abstract class Request
{
    /**
     * Get a request variable as boolean. Optionally a default parameter can be given, which is returned when the
     * parameter does not exist.
     *
     * @api
     *
     * @param string $name name of the parameter
     * @param bool $default
     *
     * @return bool
     */
    public static function getBoolParam($name, $default = false)
    {
        if (empty($name)) {
            return $default;
        } elseif (isset($_POST[$name]) && $_POST[$name]) {
            return true;
        } elseif (isset($_GET[$name]) && $_GET[$name]) {
            return true;
        }
        return $default;
    } // function


    /**
     * Get a request variable as integer. Optionally a default parameter can be  given, which is returned when the
     * parameter does not exist.
     *
     * @api
     *
     * @param string $name name of the parameter
     * @param int $default
     *
     * @return int
     */
    public static function getIntParam($name, $default = 0)
    {
        if (empty($name)) {
            return $default;
        } elseif (isset($_POST[$name])) {
            return (int)$_POST[$name];
        } elseif (isset($_GET[$name])) {
            return (int)$_GET[$name];
        }
        return $default;
    } // function


    /**
     * Get a request variable as string. Optionally a default parameter can be given, which is returned when the
     * parameter does not exist.
     *
     * @api
     *
     * @param string $name name of the parameter
     * @param string $default
     *
     * @return string
     */
    public static function getStrParam($name, $default = '')
    {
        if (empty($name)) {
            return $default;
        } elseif (isset($_POST[$name])) {
            return $_POST[$name];
        } elseif (isset($_GET[$name])) {
            return $_GET[$name];
        }
        return $default;
    } // function


    /**
     * Get a request variable as array. Optionally a default parameter can be added, which is returned when the
     * parameter does not exist or is no array.
     *
     * @api
     *
     * @param string $name name of the parameter
     * @param array $default
     *
     * @return array maybe also another type depending on the default parameter
     */
    public static function getArrayParam($name, $default = [])
    {
        if (empty($name)) {
            return $default;
        } elseif (isset($_POST[$name]) && is_array($_POST[$name])) {
            return $_POST[$name];
        } elseif (isset($_GET[$name]) && is_array($_GET[$name])) {
            return $_GET[$name];
        }
        return $default;
    } // function


    /**
     * Get a file parameter <input type="file" name="x" />. Optionally a default parameter can be given, which is
     * returned when the parameter does not exist
     *
     * @api
     *
     * @param string $name name of the parameter
     * @param mixed $default
     *
     * @return array
     */
    public static function getFileParam($name, $default = [])
    {
        if (empty($name)) {
            return $default;
        } elseif (isset($_FILES[$name]) && !empty($_FILES[$name]['tmp_name'])) {
            return $_FILES[$name];
        }
        return $default;
    } // function


    /**
     * Get current Request IP.
     *
     * @api
     *
     * @return string
     */
    public static function getIp()
    {
        $ipAddress = null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ipAddress = $_SERVER['REMOTE_ADDR'];
            }
        }

        return filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : '';
    } // function


    /**
     * Returns true if the current request is of the given $method, returns false otherwise
     *
     * @internal
     *
     * @param string $method POST, GET, DELETE, PUT
     */
    private static function isMethod($method)
    {
        return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === $method;
    } // function


    /**
     * Ensure that the request if of type $method. Throws an Exception if not.
     *
     * @internal
     *
     * @param string $method POST, GET, DELETE, PUT
     */
    private static function requires($method)
    {
        if (!self::isMethod($method)) {
            if (isset($_SERVER['REQUEST_METHOD'])) {
                throw new exception\UnsupportedRequestMethod(
                    'A '.$method.' request is required, but got a "'.$_SERVER['REQUEST_METHOD'].'" request instead'
                );
            } else {
                throw new exception\UnsupportedRequestMethod(
                    'A '.$method.' request is required, but got something undefined instead.'
                );
            }
        }
    } // function


    /**
     * Returns true if the current request is GET, returns false otherwise
     *
     * @api
     */
    public static function isGet()
    {
        return self::isMethod('GET');
    } // function


    /**
     * Ensure that the request if of type GET. Throws an Exception if not.
     *
     * @api
     */
    public static function requiresGet()
    {
        self::requires('GET');
    } // function


    /**
     * Returns true if the current request is POST, returns false otherwise
     *
     * @api
     */
    public static function isPost()
    {
        return self::isMethod('POST');
    } // function


    /**
     * Ensure that the request if of type POST. Throws an Exception if not.
     *
     * @api
     */
    public static function requiresPost()
    {
         self::requires('POST');
    } // function


    /**
     * Returns true if the current request is PUT, returns false otherwise
     *
     * @api
     */
    public static function isPut()
    {
        return self::isMethod('PUT');
    } // function


    /**
     * Ensure that the request if of type PUT. Throws an Exception if not.
     *
     * @api
     */
    public static function requiresPut()
    {
        self::requires('PUT');
    } // function


    /**
     * Returns true if the current request is DELETE, returns false otherwise
     *
     * @api
     */
    public static function isDelete()
    {
        return self::isMethod('DELETE');
    } // function


    /**
     * Ensure that the request if of type DELETE. Throws an Exception if not.
     *
     * @api
     */
    public static function requiresDelete()
    {
        self::requires('DELETE');
    } // function


    /**
     * Get Result from JSON-data which.
     *
     * @return mixed
     */
    public static function getJson()
    {
        return json_decode(file_get_contents("php://input"), true);
    } // function
}// class
