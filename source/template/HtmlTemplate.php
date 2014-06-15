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

namespace FeM\sPof\template;

use FeM\sPof\Application;
use FeM\sPof\Config;
use FeM\sPof\Logger;
use FeM\sPof\exception\SmartyTemplateException;
use FeM\sPof\FileUtil;

/**
 * Handle HTML templates using smarty.
 *
 * @package FeM\sPof\template
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
class HtmlTemplate extends \Smarty
{

    /**
     * Default config.
     *
     * @api
     *
     * @var array
     */
    protected static $defaultConfig = [
        'compile_dir' => '/tmp/smarty_compile', // will get prepended by App file root in constructor
        'cache_dir' => '/tmp/smarty_cache',
        'file_perms' => 0644,
        'dir_perms' => 0755
    ];


    /**
     * Get a instance.
     *
     * @api
     *
     * @return HtmlTemplate
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new HtmlTemplate();
        }
        return $instance;
    } // function


    /**
     * @internal
     *
     * @todo get rid of registerClass
     */
    public function __construct()
    {
        parent::__construct();

        self::$defaultConfig['compile_dir'] = Application::$FILE_ROOT.'tmp/smarty_compile';
        self::$defaultConfig['cache_dir'] = Application::$FILE_ROOT.'tmp/smarty_cache';

        $config = Config::get('smarty', self::$defaultConfig);

        $this->setTemplateDir(dirname(dirname(__DIR__)).'/template/html');
        $this->addTemplateDir(dirname(Application::$WEB_ROOT).'/template');

        $this->addPluginsDir(__DIR__.'/smarty_plugins/');
        $this->error_reporting = (E_ALL & ~E_NOTICE);
        $this->_file_perms = $config['file_perms'];
        $this->_dir_perms = $config['dir_perms'];

        // cache dir
        FileUtil::makedir($config['cache_dir'], $config['dir_perms']);
        $this->setCacheDir($config['cache_dir']);

        // compile dir
        FileUtil::makedir($config['compile_dir'], $config['dir_perms']);
        $this->setCompileDir($config['compile_dir']);

        // add classes for usage in templates
        $this->registerClass('Event', 'FeM\\sPof\\model\\Event');
        $this->registerClass('Session', 'FeM\\sPof\\Session');
        $this->registerClass('Group', 'FeM\\sPof\\model\\Group');
        $this->registerClass('DBConnection', 'FeM\\sPof\\model\\DBConnection');
        $this->registerClass('SessionRegister', 'FeM\\sPof\\model\\SessionRegister');
        $this->registerClass('Config', 'FeM\\sPof\\Config');
        $this->registerClass('Authorization', 'FeM\\sPof\\Authorization');
        $this->registerClass('Auth', 'FeM\\sPof\\Authorization');
    } // constructor


    /**
     * fetches a rendered Smarty template. Overrides Smarty_Internal_Compilerbase::fetch().
     *
     * @api
     *
     * @throws \ErrorException if a error occurred in the code triggered by the template
     * @throws \FeM\sPof\exception\SmartyTemplateException if the error occurred in a template
     *
     * @param string $template (optional) the resource handle of the template file or template object
     * @param mixed $cache_id (optional) cache id to be used with this template
     * @param mixed $compile_id (optional) compile id to be used with this template
     * @param object $parent (optional) next higher level of Smarty variables
     * @param bool $display (optional) true: display, false: fetch
     * @param bool $merge_tpl_vars (optional) if true parent template variables merged in to local scope
     * @param bool $no_output_filter (optional) if true do not run output filter
     *
     * @return string rendered template output
     */
    public function fetch(
        $template = null,
        $cache_id = null,
        $compile_id = null,
        $parent = null,
        $display = false,
        $merge_tpl_vars = true,
        $no_output_filter = false
    ) {
        try {
            if ($display) {
                Logger::getInstance()->addSmarty();
            }
            return parent::fetch(
                $template,
                $cache_id,
                $compile_id,
                $parent,
                $display,
                $merge_tpl_vars,
                $no_output_filter
            );
        } catch (\ErrorException $e) {
            if (strpos($e->getFile(), '.tpl.php') > 0) {
                $file = HtmlTemplate::getTemplateByCompilePath($e->getFile());
                $error_msg = str_replace(
                    'Undefined index: ',
                    'Unassigned variable: $',
                    $e->getMessage()
                ) . ' in File: '.$file.' in Compiled file '.$e->getFile().':'.$e->getLine();
                $e = new SmartyTemplateException($error_msg, $file, $e);
            }
            throw $e;
        }
    } // function


    /**
     * Get the template name from the compiled file.
     *
     * @internal
     *
     * @param string $path compile path
     * @return string template name
     */
    private static function getTemplateByCompilePath($path)
    {
        $data = file_get_contents($path, false, null, - 1, 200);
        $data = str_replace('<', '', $data);
        return preg_replace('/^.*compiled from "(.+)".*$/s', '$1', $data);
    } // function


    /**
     * This function just returns the param. The use of the function is to be able, to check usage of templates in
     * tests. Nothing more.
     *
     * @api
     *
     * @param string $path delegated path
     * @return string
     */
    public static function delegate($path)
    {
        return $path;
    } // function
}// class
