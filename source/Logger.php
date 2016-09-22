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

/**
 * Logger interface.
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
class Logger implements \Psr\Log\LoggerInterface
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

        if(Application::$LOGFILE &&
            (file_exists(Application::$LOGFILE) && is_writable(Application::$LOGFILE) ||
            !file_exists(Application::$LOGFILE && is_writable(dirname(Application::$LOGFILE))))) {
            $this->logfile_handle = fopen(Application::$LOGFILE,'a+');
        }
    } // function


    /**
     * log message to the session.
     *
     * @param string $message
     * @param string $severity
     */
    private function sessionLog($message, $severity)
    {
        static $levels;
        if (!isset($levels)) {
            $levels = Config::get('log_level', self::$defaultConfig);
        }

        if (in_array($severity, $levels)) {
            if(!in_array($severity, ['debug', 'dump'])) {
                Session::addErrorMsg($message);
            }

            if(is_resource($this->logfile_handle)) {
                $line = sprintf('[%1$s] %2$s: %3$s', strftime('%c'), strtoupper($severity), $message);
                fwrite($this->logfile_handle, $line."\n");
            } elseif (php_sapi_name() == "cli") {
                echo sprintf('[%1$s] %2$s: %3$s', strftime('%c'), strtoupper($severity), $message)."\n";
            }
        }
    } // function


    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = [])
    {
        $this->sessionLog($message, 'emergency');
        $this->debugBar['messages']->emergency($message, $context);
    } // function


    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = [])
    {
        $this->sessionLog($message, 'alert');
        $this->debugBar['messages']->alert($message, $context);
    } // function


    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = [])
    {
        $this->sessionLog($message, 'critical');
        $this->debugBar['messages']->critical($message, $context);
    } // function


    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = [])
    {
        $this->sessionLog($message, 'error');
        $this->debugBar['messages']->error($message, $context);
    } // function


    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = [])
    {
        $this->sessionLog($message, 'warning');
        $this->debugBar['messages']->warning($message, $context);
    } // function


    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = [])
    {
        $this->sessionLog($message, 'notice');
        $this->debugBar['messages']->notice($message, $context);
    } // function


    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        $this->sessionLog($message, 'info');
        $this->debugBar['messages']->info($message, $context);
    } // function


    /**
     * {@inheritdoc}
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = [])
    {
        $this->sessionLog($message, 'debug');
        $this->debugBar['messages']->debug($message, $context);
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
        $this->sessionLog($message, $level);
        $this->debugBar['messages']->log($level, $message, $context);
    } // function


    /**
     * Start Measuring a operation.
     *
     * @param string $operation
     * @param string $description
     */
    public function traceStart($operation, $description = '')
    {
        $this->debugBar['time']->startMeasure($operation, $description);

        $this->sessionLog($operation . (!empty($description) ? ': '.$description : ''), 'trace');
    } // function


    /**
     * Stop Measuring a operation, which was started by traceStart.
     *
     * @param string $operation
     */
    public function traceStop($operation)
    {
        $this->debugBar['time']->stopMeasure($operation);
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
        Session::addErrorMsg($exception->getMessage());
        $this->debugBar['exceptions']->addException($exception);

        if(is_resource($this->logfile_handle)) {
            $line = sprintf('[%1$s] %2$s: %3$s', strftime('%c'), 'FATAL', get_class($exception). ': '.$exception->getMessage());
            fwrite($this->logfile_handle, $line."\n");
        }
    } // function


    /**
     * Add var_export of given variable as debug message.
     * @param mixed $variable
     */
    public function dump($variable)
    {
        $this->sessionLog(var_export($variable, true), 'dump');
        $this->debugBar['dumps']->debug(var_export($variable, true));
    } // function


    /**
     * Add var_export of given variable as debug message.
     * @param mixed $message
     */
    public function auth($message)
    {
        $this->sessionLog($message, 'auth');
        $this->debugBar['auth']->debug($message);
    } // function


    /**
     * Get JavaScript renderer.
     *
     * @return \DebugBar\JavascriptRenderer
     */
    public function getRenderer()
    {
        return $this->debugBar->getJavascriptRenderer();
    } // function


    /**
     * Stack data for the next request.
     */
    public function stackData()
    {
        return $this->debugBar->stackData();
    } // function


    /**
     * Add current smarty settings to the log.
     */
    public function addSmarty()
    {
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
