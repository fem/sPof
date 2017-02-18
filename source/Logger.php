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

use DebugBar\DataCollector as Collector;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Logger interface.
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
class Logger extends AbstractLogger
{

    /**
     * Default config.
     *
     * @api
     *
     * @var array
     */
    private static $defaultConfig = ['warning', 'error', 'critical', 'alert', 'emergency'];

    /**
     * Reference to the DebugBar.
     *
     * @internal
     *
     * @var \DebugBar\DebugBar
     */
    public $debugBar;


    /**
     * Reference to file handle of the logfile.
     *
     * @internal
     *
     * @var resource
     */
    private $logfile_handle;

    /**
     * Get a new instance.
     *
     * @return Logger
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new self();
        }
        return $instance;
    } // function


    /**
     * Create a new instance.
     *
     * @internal
     */
    private function __construct()
    {
        if(Application::$LOGFILE &&
            (file_exists(Application::$LOGFILE) && is_writable(Application::$LOGFILE) ||
            !file_exists(Application::$LOGFILE && is_writable(dirname(Application::$LOGFILE))))) {
            $this->logfile_handle = fopen(Application::$LOGFILE,'a+');
        }
    } // function

    /**
     * {@inheritdoc}
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = [])
    {
        static $levels;
        if (!isset($levels)) {
            $levels = Config::get('log_level', self::$defaultConfig);
        }

        // message seen by the user
        $user_message = $message;

        if($level == 'exception') {
            $level = LogLevel::CRITICAL;

            // get exception from context
            if(!isset($context['exception'])) {
                // no exception?!?!
                return;
            }
            $exception = $context['exception'];
            unset($context['exception']);

            // add class name to log (not user)
            $message = get_class($exception). ': '.$exception->getMessage();

            if($this->debugBar) {
                $this->debugBar['exceptions']->addException($exception);
            }
        }

        if (in_array($level, $levels)) {
            // don't show debug as user message
            if(!in_array($level, ['debug', 'dump']) && php_sapi_name() != "cli") {
                Session::addErrorMsg($user_message);
            }

            $line = sprintf('[%1$s] %2$s: %3$s', strftime('%c'), strtoupper($level), $message);
            if(is_resource($this->logfile_handle)) {
                fwrite($this->logfile_handle, $line."\n");
            } elseif (php_sapi_name() == "cli") {
                echo $line . "\n";
            }
        }

        if($this->debugBar) {
            if($level == 'auth') {
                $this->debugBar['auth']->log(LogLevel::DEBUG, $message, $context);
            } elseif($level == 'dump') {
                $this->debugBar['dumps']->log(LogLevel::DEBUG, $message, $context);
            } else {
                $this->debugBar['messages']->log($level, $message, $context);
            }
        }
    } // function


    /**
     * Start Measuring a operation.
     *
     * @param string $operation
     * @param string $description
     */
    public function traceStart($operation, $description = '')
    {
        if($this->debugBar) {
            $this->debugBar['time']->startMeasure($operation, $description);
        }
    } // function


    /**
     * Stop Measuring a operation, which was started by traceStart.
     *
     * @param string $operation
     */
    public function traceStop($operation)
    {
        if($this->debugBar) {
            $this->debugBar['time']->stopMeasure($operation);
        }
    } // function


    /**
     * Gather error info from exception and add as message.
     *
     * @param \Exception $exception
     *
     * @throws \Exception
     */
    public function exception(\Exception $exception)
    {
        $this->log('exception', $exception->getMessage(), ['exception' => $exception]);
    } // function


    /**
     * Add var_export of given variable as debug message.
     * @param mixed $variable
     */
    public function dump($variable)
    {
        $this->log('dump', var_export($variable, true));
    } // function


    /**
     * Add var_export of given variable as debug message.
     * @param mixed $message
     */
    public function auth($message)
    {
        $this->log('auth', $message);
    } // function

    /**
     * Enables debugBar and initializes data collectors
     */
    public function enableDebugbar()
    {
        if(!$this->debugBar) {
            $this->debugBar = new \DebugBar\DebugBar();
            $this->debugBar->addCollector(new Collector\MessagesCollector());
            $this->debugBar->addCollector(new Collector\MessagesCollector('dumps'));
            $this->debugBar->addCollector(new Collector\MessagesCollector('auth'));
            $this->debugBar->addCollector(new Collector\ExceptionsCollector());
            $this->debugBar->addCollector(new Collector\TimeDataCollector());
            $this->debugBar->addCollector(new Collector\RequestDataCollector());
            $this->debugBar->addCollector(new Collector\MemoryCollector());
            $this->debugBar->addCollector(new Collector\PhpInfoCollector());
            $this->debugBar->addCollector(new Collector\ConfigCollector(Config::getAll()));
        }
    }

    /**
     * Get JavaScript renderer.
     *
     * @return \DebugBar\JavascriptRenderer
     */
    public function getRenderer()
    {
        if(!$this->debugBar) {
            return null;
        }
        return $this->debugBar->getJavascriptRenderer();
    } // function


    /**
     * Stack data for the next request.
     */
    public function stackData()
    {
        if(!$this->debugBar) {
            return;
        }
        return $this->debugBar->stackData();
    } // function


    /**
     * Add current smarty settings to the log.
     */
    public function addSmarty()
    {
        if(!$this->debugBar) {
            return;
        }

        $vars = template\HtmlTemplate::getInstance()->getTemplateVars();
        foreach ($vars as &$var) {
            if ($var === '') {
                $var = '(empty)';
            } elseif ($var === false) {
                $var = '(bool: false)';
            } elseif ($var === true) {
                $var = '(bool: true)';
            } elseif ($var === null) {
                $var = '(nil)';
            }
        }
        ksort($vars);
        $this->debugBar->addCollector(
            new Collector\ConfigCollector($vars, 'Smarty')
        );
    } // function
}// class
