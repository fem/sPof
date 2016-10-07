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

require_once __DIR__.'/_functions.php';

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
     * Reference to the global cache root folder
     *
     * @api
     *
     * @var string
     */
    public static $CACHE_ROOT;

    /**
     * Reference to the global cache root folder
     *
     * @api
     *
     * @var string
     */
    public static $LOGFILE = '';

    /**
     * Base path used for creating URLs
     *
     * @var string
     */
    public static $BASE_PATH = '';

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
     * Name of the default module Name.
     *
     * @var string
     */
    private $defaultModule = '';


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

        self::$NAMESPACE = rtrim($namespace,'\\') . '\\';

        if ($file_root === null) {

            // guess for default composer installation
            self::$FILE_ROOT = dirname(dirname(dirname(dirname(__DIR__))));
        } else {
            self::$FILE_ROOT = $file_root;
        }
        self::$FILE_ROOT = rtrim(self::$FILE_ROOT, '/').'/';
        self::$WEB_ROOT = self::$FILE_ROOT.'public/';
        self::$CACHE_ROOT = self::$FILE_ROOT.'tmp/';

        self::$INSTANCE = $this;

        //var_dump(\FeM\sPof\model\DBConnection::getInstance());
        if (!\FeM\sPof\model\DBConnection::isOnline()) {
            die(_('No Datebase connection.'));
        }
    } // function


    /**
     * Run the application.
     *
     * @api
     */
    public function dispatch()
    {
        # get current context from URL
        try {
            Router::resolve(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/');
            Router::updateHtaccess();
            #Router::updateRules();
        } catch (\Exception $e) {
            Logger::getInstance()->exception($e);
        }

        // from here we'll capture every output
        ob_start();

        // we need the session this early, (feel free to remove the following line and get stuck with session problems)
        Session::getInstance();

        $module = Router::getModule();
        $action = Router::getAction();

        $view = $this->handleRequest($module, $action);

        // threat all unwanted output as error output
        $errors = ob_get_clean();
        if (trim($errors) !== '') {
            Logger::getInstance()->warning($e);
        }

        // finally deliver page
        $view->display();
    } // function


    /**
     * Prepare non-application code to run with the framework code.
     *
     * @api
     */
    public static function bootstrap()
    {
        new self();
    }

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
        // don't modify empty or full qualified class names
        if(empty($module) || $module[0] == '\\') {
            return $module;
        }
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
     * @param string $action
     *
     * @return view\AbstractView
     */
    private function handleRequest($module, $action)
    {
        // early check, whether we were able to resolve a route
        if (empty($module)) {
            $viewName = $this->getClassByModule($this->defaultModule);
            if (method_exists($viewName, 'handleNoViewFound')) {
                return $viewName::handleNoViewFound();
            }
        }

        // there will always be something to view (otherwise we should get a nice error message)
        $viewName = $this->getClassByModule($module);

        /** @var \FeM\sPof\view\AbstractView $view */
        $view = null;
        try {

            // for an action, there is a controller to handle it
            if ($action) {
                AbstractController::createAndRunAction($module, $action);
            }

            if (!class_exists($viewName)) {

                // if file exists -> the class name does not match the filename
                if (file_exists('view/'.$module.'View.php')) {
                    throw new \FeM\sPof\exception\ClassNotFoundException(__(
                        '"%sView" requested, but missing definition in file "view/%sView.php", maybe a typo in the '
                        .'class name?',
                        $module,
                        $module
                    ));
                }

                // file doesn't exist -> file probably need to get renamed
                if (!file_exists('view/'.$module.'View.php')) {
                    throw new \FeM\sPof\exception\ClassNotFoundException(__(
                        '"%sView" requested, but could not find the associated file "view/%sView.php"',
                        $module,
                        $module
                    ));
                }
            }

            // class does exist, so get us a instance of it
            $view = new $viewName();
            $view->executeShow();
        } catch (\Exception $e) {
            Logger::getInstance()->warning(__("Could not instanciate class of type '%s'. Trying to find alternatives", $viewName));

            // try to call the static implementation, we need this workaround, because this method is called staticly,
            // directly on this class, so no late state binding, try to workaround by directly calling it and otherwise
            // use local fallback
            if (method_exists($viewName, 'handleException')) {
                $viewName::handleException($e);
            } else {
                $viewName = $this->getClassByModule($this->defaultModule);
                if (method_exists($viewName, 'handleException')) {
                    $viewName::handleException($e);
                }
            }
        }

        // we got a view we can proceed
        if ($view instanceof view\AbstractView) {
            return $view;
        }

        // try to call the static implementation, we need this workaround, because this method is called staticly,
        // directly on this class, so no late state binding, try to workaround by directly calling it and otherwise
        // use local fallback
        if (method_exists($viewName, 'handleNoViewFound')) {
            return $viewName::handleNoViewFound();
        } else {
            $viewName = $this->getClassByModule($this->defaultModule);
            if (method_exists($viewName, 'handleNoViewFound')) {
                return $viewName::handleNoViewFound();
            }
        }

        if (empty($module)) {
            self::death(_("\nNo module given, abort."));
        } else {
            self::death(__("\nCould not find a suitable view component for module '%s'.", $module));
        }
    } // function


    /**
     * In case there is an unrecoverable error, this method should be called. The application will be terminated and
     * remaining error messages are flushed.
     *
     * @api
     *
     * @param string $message last message before application dies
     */
    public static function death($message = '') {

        // this code is not safe for use in different tabs
        foreach (Session::getErrorMsg() as $error) {
            echo $error['content']."\n";
        }

        die($message);
    } // function


    /**
     * Add an additional module to the current application. Will currently load additional routes, nothing more.
     *
     * @api
     *
     * @param string path directory relative to project file root
     */
    public function addModule($path)
    {
        Router::$additionalRoutes[] = $path;
    } // function


    /**
     * Set default Module name, which will be used if no or empty module name is used.
     *
     * @api
     *
     * @param string $name
     */
    public function setDefaultModule($name)
    {
        $this->defaultModule = $name;
    } // function


    /**
     * Set the locale for text translations.
     *
     * @api
     *
     * @param string $locale e.g. de_DE or en_US
     */
    public function setLocale($locale, $domain = 'spof')
    {
        putenv('LANG='.$locale);
        setlocale(LC_ALL, $locale);

        bindtextdomain('spof', __DIR__.'/locale');
        bind_textdomain_codeset($domain, 'UTF-8');

        bindtextdomain($domain, self::$FILE_ROOT.'locale');
        bind_textdomain_codeset($domain, 'UTF-8');

        textdomain($domain);
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
     * Get base path, which will be used for creating URLs.
     *
     * @api
     *
     * @param string $name
     */
    public static function getBasePath()
    {
        // late assignment, since in our constructor no other classes should be called
        if(empty(self::$BASE_PATH)) {
            self::$BASE_PATH = Config::getDetail('server','path');
        }
        return self::$BASE_PATH;
    } // function

}// class
