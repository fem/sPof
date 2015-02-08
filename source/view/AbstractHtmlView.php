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
use FeM\sPof\Router;
use FeM\sPof\Session;
use FeM\sPof\Request;
use FeM\sPof\Logger;
use FeM\sPof\model\Visitor;

use FeM\sPof\template as template;

/**
 * This class is base for all html output views.
 *
 * The constructor must not be overwridden, instead initialize() shall be used. All showing events are based on the
 * $show parameter (from url) which results in calling the showSomething method if $show was 'something'. Decisions on
 * what should be displayed are based on the "$show" parameter. After initialization the corresponding show-Method is
 * called from the class constructor. Mapping: $show='something' -> call "something()".
 *
 * @package FeM\sPof\view
 * @author dangerground
 * @author pegro
 * @since 1.0
 */
abstract class AbstractHtmlView extends AbstractView
{
    /**
     * Hold current set of JavaScript files.
     *
     * @internal
     *
     * @var array
     */
    private $jsFiles = [];

    /**
     * Hold current set of CSS files.
     *
     * @internal
     *
     * @var array
     */
    private $cssFiles = [];

    /**
     * Reference to the current template
     *
     * @internal
     *
     * @var template\HtmlTemplate
     */
    private $template;

    /**
     * Current Authentication context.
     *
     * @api
     *
     * @var \FeM\sPof\Authorization
     */
    protected $auth;


    /**
     * Show a not forbidden page.
     *
     * @api
     */
    public static function sendForbidden()
    {
        template\HtmlTemplate::getInstance()->display('http_error/403.tpl');
        parent::sendForbidden();
    } // function


    /**
     * Show a not found page.
     *
     * @api
     */
    public static function sendNotFound()
    {
        template\HtmlTemplate::getInstance()->display('http_error/404.tpl');
        parent::sendNotFound();
    } // function


    /**
     * Show a internal server error page.
     *
     * @api
     */
    public static function sendInternalError()
    {
        template\HtmlTemplate::getInstance()->display('http_error/500.tpl');
        parent::sendInternalError();
    } // function


    /**
     * The constructor prepares everything for html output and starts the processing chain (calls initialize() and the
     * show-method from current context).
     *
     * @internal
     */
    public function __construct()
    {
        // update css-rules from stylesheet directory
        template\CssTemplate::update();


        $this->trackStandardSession();
        header('Content-Type: text/html; charset=utf-8');

        $this->template = template\HtmlTemplate::getInstance();

        $this->auth = Authorization::getInstance();
        $this->assignByRef('auth', $this->auth);

        // template default values
        $this->assign('ogmetadata', []);
        $this->assign('success', []);
        $this->assign('errors', []);
        $this->assign('content', '');

        // call initializer
        $this->initializeViewtype();

        // initialize form
        if (method_exists($this, 'initializeForm')) {
            $this->initializeForm();
        }
    } // constructor


    /**
     * This method is used to initialize inheriting classes, by default the initialize() method is called, so should
     * any inheriting class (or at least call this function again).
     *
     * @api
     */
    protected function initializeViewtype()
    {
        $this->initialize();
    } // function


    /**
     * Execute the show method.
     *
     * @internal
     *
     * @param string $show
     * @return string
     */
    public function executeShow($show = null)
    {
        if ($show === null) {
            $show = Router::getShow();
        }

        // by default use the content from the show method or the form
        $content = parent::executeShow($show);

        // if still empty, guess there is a template
        if ($content === null) {
            $templateFile = '';
            if (isset(static::$TEMPLATE_DIR)) {
                $templateFile = static::$TEMPLATE_DIR.'/';
            } else {
                $templateFile = lcfirst(Router::getModule()).'/';
            }
            $templateFile .= $show.'.tpl';
            try {
                $content = template\HtmlTemplate::getInstance()->fetch($templateFile);
            } catch (\SmartyException $e) {
                Logger::getInstance()->info(_s(
                    'Could not find template, next trying form. (%s)',
                    $e->getMessage()
                ));
            }
        }

        // if still empty, try to generate by form
        if (isset($this->form) && $this->form->isActive()) {
            $content .= $this->form->render();
        }
        // call show method
        $this->assign('content', $content);

        return $content;
    } // function


    /**
     * Handle if a exception was thrown during execution of show or initialization of the class.
     *
     * @internal
     *
     * @param \Exception $exception
     * @return AbstractView|void
     */
    public static function handleException(\Exception $exception)
    {
        if ($exception instanceof \FeM\sPof\exception\InvalidParameterException) {
            foreach ($exception->getParameters() as $parameter) {
                Session::addErrorMsg($parameter['name'].' - '.$parameter['description']);
            }
        }

        Logger::getInstance()->exception($exception);
        return null;
    } // function


    /**
     * This class is used as constructor for all deferred classes. Feel free to overwrite.
     *
     * @api
     */
    protected function initialize()
    {
        // do nothing, instead do something in defered classes
    } // function


    /**
     * Assign a value to a template variable.
     *
     * @api
     *
     * @param string $variable template variable name
     * @param mixed $value value to assign
     */
    final protected function assign($variable, $value)
    {
        $this->template->assign($variable, $value);
    } // function


    /**
     * Assign a reference of a variable to a template variable.
     *
     * @api
     *
     * @param string $variable template variable name
     * @param mixed $value reference to assign
     */
    final protected function assignByRef($variable, &$value)
    {
        $this->template->assignByRef($variable, $value);
    } // function


    /**
     * Append a value to a template array named $variable.
     *
     * @api
     *
     * @param string $variable template variable name
     * @param mixed $value value to assign
     */
    final protected function append($variable, $value)
    {
        $this->template->append($variable, $value);
    } // function


    /**
     * Append another JavaScript file.
     *
     * @api
     *
     * @param string $file filename based on javascript/ directory
     * @param bool $relative file is relative to public/js/ directory
     */
    final protected function addJavascript($file, $relative = false)
    {
        $this->jsFiles[] = ['name' => $file, 'relative' => $relative];
    } // function


    /**
     * Append another JavaScript file.
     *
     * @api
     *
     * @param string $file filename based on javascript/ directory
     */
    private function addInternalJavascript($file)
    {
        $this->addJavascript(dirname(dirname(__DIR__)).'/template/js/'.$file);
    } // function


    /**
     * Append another CSS file.
     *
     * @api
     *
     * @param string $file filename without css based on the public/css folder and without '.css' suffix.
     */
    final protected function addStylesheet($file)
    {
        if (strpos($file, '.css') === false) {
            $this->cssFiles[] = ['name' => \FeM\sPof\Application::$WEB_ROOT.'css/'.$file.'.css', 'fixpaths' => false];
        } else {
            $this->cssFiles[] = ['name' => $file, 'fixpaths' => true];
        }
    } // function


    /**
     * Append another CSS file.
     *
     * @api
     *
     * @param string $file filename without css based on the public/css folder and without '.css' suffix.
     */
    final protected function removeStylesheet($name)
    {
        foreach ($this->cssFiles as $key => $file) {
            if ($file['name'] === $name || $file['name'] === \FeM\sPof\Application::$WEB_ROOT.'css/'.$name.'.css') {
                unset($this->cssFiles[$key]);
            }
        }
    } // function



    /**
     * adds <meta property="og:$property" content="$content" /> to current page
     *
     * @api
     *
     * @param string  $property
     * @param string  $content
     */
    final protected function addOgMeta($property, $content)
    {
        $this->append('ogmetadata', ['property' => $property, 'content' => $content]);
    } // function


    /**
     * Add some required JavaScript libraries and theme files for jquery usage.
     *
     * @api
     */
    final protected function useJquery()
    {
        $this->addInternalJavascript('jquery.js');
    } // function

    /**
     * Add some required JavaScript libraries and theme files for jquery-UI usage.
     *
     * @api
     */
    final protected function useJqueryUi()
    {
        $this->useJquery();
        $this->addInternalJavascript('jquery.ui.js');
        $this->addInternalJavascript('jquery.timepicker.js');
        $this->addInternalJavascript('useJqueryUi.js');

        $path = dirname(dirname(__DIR__)).'/template/vendor/';
        $this->addStylesheet($path.'jquery-ui-1.10.4.custom/css/le-frog/jquery-ui-1.10.4.custom.css');
        $this->addStylesheet($path.'jquery-timepicker-master/jquery.timepicker.css');
        #$this->addStylesheet($path.'../resource/jquery-ui-theme/jquery-ui-timepicker-addon');
    } // function


    /**
     * Add some required JavaScript libraries wygiwyg.
     *
     * @api
     */
    final protected function useWysiwyg()
    {
        $this->useJqueryUi();
        $this->addInternalJavascript('wysihtml5.js');
        $this->addInternalJavascript('wysiwyg.js');
    } // function


    /**
     * Add some required JavaScript libraries and theme files for jquery-UI usage.
     *
     * @api
     */
    final protected function useFancyBox()
    {
        $this->useJquery();
        $this->addInternalJavascript('jquery.fancybox.js');
        $this->addInternalJavascript('fancybox.js');

        $this->addStylesheet(dirname(dirname(__DIR__)).'/template/vendor/fancyBox-master/source/jquery.fancybox.css');
    } // function

    /**
     * include javascript and stylesheets for the debugbar.
     *
     * @api
     */
    final protected function useDebugBar()
    {
        $this->useJquery();

        $path = dirname(dirname(dirname(dirname(__DIR__)))).'/maximebf/debugbar/src/DebugBar/Resources/';

        $this->addJavascript($path.'debugbar.js');
        $this->addJavascript($path.'openhandler.js');
        $this->addJavascript($path.'widgets.js');

        $this->addStylesheet($path.'debugbar.css');
        $this->addStylesheet($path.'openhandler.css');
        $this->addStylesheet($path.'widgets.css');

        $this->assign('debugbar', Logger::getInstance()->getRenderer());
    } // function


    /**
     * This function is the final step to render a website, it passes the final arguments to the template, including
     * all error messages, gathered so far. And finally flushes the website content.
     *
     * @api
     */
    public function display()
    {
        // minify js
        $jsFile = template\JavascriptTemplate::combine($this->jsFiles);
        if ($jsFile !== false) {
            $this->assign('customjsfile', [$jsFile]);
        } else {
            $this->assign('customjsfile', []);
        }

        // minify css
        $cssFile = template\CssTemplate::combine($this->cssFiles);
        if ($cssFile !== false) {
            $this->assign('customcssfile', [$cssFile]);
        } else {
            $this->assign('customcssfile', []);
        }

        // this code is not safe for use in different tabs
        foreach (Session::getErrorMsg() as $error) {
            $this->append('errors', ($error['field']?'"'.$error['field'].'" - ':'').$error['content']);
        }
        foreach (Session::getSuccessMsg() as $success) {
            $this->append('success', $success);
        }

        // we need a relative dir from smarty templates
        template\HtmlTemplate::getInstance()->display('layout.tpl');

        // after we've displayed them, we may reset them
        Session::resetErrorMsg();
        Session::resetSuccessMsg();
    } // function
}// class
