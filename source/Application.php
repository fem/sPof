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

use FeM\sPof\dav\CalendarHandler;
use FeM\sPof\notification\NotificationHandler;
use FeM\sPof\notification\NotificationTargetHandler;

/**
 * Class to control & run the application.
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
class Application
{
    /**
     * Currently used namespace of the application.
     *
     * @api
     *
     * @var string
     */
    public static $NAMESPACE = '';

    /**
     * Reference to the file root folder of the application.
     *
     * @api
     *
     * @var string
     */
    public static $FILE_ROOT;

    /**
     * Reference to the web root folder
     *
     * @api
     *
     * @var string
     */
    public static $WEB_ROOT;

    /**
     * Reference to thec currently used Application class instance.
     *
     * @api
     *
     * @var Application
     */
    private static $INSTANCE;

    /**
     * List of authorization handlers.
     *
     * @internal
     *
     * @var array
     */
    private $authorizationHandler = [];

    /**
     * List of notification protocol handlers.
     *
     * @internal
     *
     * @var array
     */
    private $notificationHandler = [];

    /**
     * Lift of notification target group handlers.
     *
     * @var array
     */
    private $notificationTargetHandler = [];

    /**
     * List of calendar handlers.
     *
     * @var array
     */
    private $calendarHandler = [];


    /**
     * Create a new Application instance.
     *
     * @api
     *
     * @param string $namespace (optional) namespace with trailing backslash
     * @param string $file_root (optional) location to the file root of the application, if null it will be guessed
     */
    public function __construct($namespace = '\\', $file_root = null)
    {
        // we want our own error handler
        set_error_handler(__CLASS__.'::errorHandler');

        self::$NAMESPACE = $namespace;

        if ($file_root === null) {
            self::$FILE_ROOT = dirname(dirname(__DIR__));
        }
        self::$FILE_ROOT = rtrim(self::$FILE_ROOT, '/').'/';
        self::$WEB_ROOT = self::$FILE_ROOT.'public/';

        self::$INSTANCE = $this;
    } // function


    /**
     * Run the application.
     *
     * @api
     */
    public function dispatch()
    {
        # get current context from URL
        Router::resolve(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/');
        Router::updateHtaccess();
        #Router::updateRules();

        // from here we'll capture every output
        ob_start();

        // we need the session this early, (feel free to remove the following line and get stuck with session problems)
        Session::getInstance();

        $module = Router::getModule();
        $action = Router::getAction();

        // for an action, there is a controller to handle it
        if ($action) {
            AbstractController::createAndRunAction($module, $action);
        }

        $view = $this->getView($module);

        // threat all unwanted output as error output
        $errors = ob_get_clean();
        if (trim($errors) !== '') {
            Session::addErrorMsg($errors);
        }

        // finally deliver page
        $view->display();
    } // function


    /**
     * Start a new notification dispatcher bot.
     *
     * @api
     */
    public function notificationDispatch()
    {
        require dirname(__DIR__).'bin/notificationBot.php';
    } // function


    /**
     * Always throw an exception instead of an error.
     *
     * @internal
     *
     * @throws \ErrorException on purpose
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     *
     * @return bool false, if error is recoverable
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // exceptions for file handling (used e.g. by smarty)
        if (strpos($errstr, 'filemtime(): stat failed for ') === 0 || strpos($errstr, 'unlink(') === 0) {

            // tell php to recover from error
            return false;
        }

        // recover from error by throwing an exception
        throw new \ErrorException($errstr.'-------'.$errfile.':'.$errline, 0, $errno, $errfile, $errline);
    } // function


    /**
     * Factory to get a view by module name. If the desired view is not available then an alternative view might get
     * returned.
     *
     * @internal
     *
     * @param string $module module name
     *
     * @throws \FeM\sPof\exception\NotImplementedException
     * @return string reference to class name
     */
    private function getClassByModule($module)
    {
        return self::$NAMESPACE.'view\\'.$module.'View';
    } // function


    /**
     * Factory to get a view by module name. If the desired view is not available then an alternative view might get
     * returned.
     *
     * @internal
     *
     * @throws \Exception
     *
     * @param string $module module name
     *
     * @return view\AbstractView
     */
    private function getView($module)
    {
        // there will always be something to view (otherwise we should get a nice error message)
        $viewName = $this->getClassByModule($module);
        try {
            if (!class_exists($viewName)) {

                // if file exists -> the class name does not match the filename
                if (file_exists('view/'.$module.'View.php')) {
                    throw new \FeM\sPof\exception\ClassNotFoundException(
                        '"'.$module.'View" requested, but missing definition in file "view/'.$module.'View.php", '
                        .'maybe a typo in the class name?'
                    );
                }

                // file doesn't exist -> file probably need to get renamed
                if (!file_exists('view/'.$module.'View.php')) {
                    throw new \FeM\sPof\exception\ClassNotFoundException(
                        '"'.$module.'View" requested, but could not find the associated file "view/'.$module.'View.php"'
                    );
                }
            }

            // class does exist, so get us a instance of it
            return new $viewName();
        } catch (\Exception $e) {

            // try to call the static implementation, we need this workaround, because this method is called staticly,
            // directly on this class, so no late state binding, try to workaround by directly calling it and otherwise
            // use local fallback
            if (method_exists($viewName, 'handleException')) {
                $viewName::handleException($e);
            }
        }

        // on failure load default view for errors
        if (!isset($view)) {

            // try to call the static implementation, we need this workaround, because this method is called staticly,
            // directly on this class, so no late state binding, try to workaround by directly calling it and otherwise
            // use local fallback
            if (method_exists($viewName, 'handleNoViewFound')) {
                return $viewName::handleNoViewFound();
            }
        }

        die("Could not find a suitable view component.");
    } // function


    /**
     * Add a new authorization handler.
     *
     * @api
     *
     * @param AuthorizationHandler $handler
     */
    public function addAuthorizationHandler(AuthorizationHandler $handler)
    {
        $this->authorizationHandler[] = $handler;
    } // function


    /**
     * Get an array of authorization handlers.
     *
     * @api
     *
     * @return AuthorizationHandler[]
     */
    public static function getAuthorizationHandlers()
    {
        return self::$INSTANCE->authorizationHandler;
    } // function


    /**
     * Add a new notification protocol handler.
     *
     * @api
     *
     * @param NotificationHandler $handler
     */
    public function addNotificationHandler(NotificationHandler $handler)
    {
        $this->notificationHandler[] = $handler;
    } // function


    /**
     * Return an array of notification protocol handlers.
     *
     * @api
     *
     * @return NotificationHandler[]
     */
    public static function getNotificationHandlers()
    {
        return self::$INSTANCE->notificationHandler;
    } // function


    /**
     * Add a new notification target group handler.
     *
     * @api
     *
     * @param NotificationTargetHandler $handler
     */
    public function addNotificationTargetHandler(NotificationTargetHandler $handler)
    {
        $this->notificationTargetHandler[] = $handler;
    } // function


    /**
     * Return an array of notification target group handlers.
     *
     * @api
     *
     * @return NotificationTargetHandler[]
     */
    public static function getNotificationTargetHandlers()
    {
        return self::$INSTANCE->notificationTargetHandler;
    } // function


    /**
     * Add a new calendar handler.
     *
     * @api
     *
     * @param CalendarHandler $handler
     */
    public function addCalendarHandler(CalendarHandler $handler)
    {
        $this->calendarHandler[] = $handler;
    } // function


    /**
     * Get an array of calendar handlers.
     *
     * @api
     *
     * @return CalendarHandler[]
     */
    public static function getCalendarHandlers()
    {
        return self::$INSTANCE->calendarHandler;
    } // function
}// class
