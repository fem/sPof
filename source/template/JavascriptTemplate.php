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
use FeM\sPof\Logger;
use FeM\sPof\FileUtil;

/**
 * Handle JavaScript templates using smarty.
 *
 * @package FeM\sPof\template
 * @author dangerground
 * @since 1.0
 */
class JavascriptTemplate extends HtmlTemplate
{
    /**
     * Get path to which the file should be put to.
     *
     * @internal
     *
     * @return string
     */
    private static function getTargetPath()
    {
        return Application::$WEB_ROOT.'js/';
    } // function


    /**
     * Get path from where to original files are.
     *
     * @internal
     *
     * @return string
     */
    private static function getSourcePath()
    {
        return Application::$FILE_ROOT.'javascript/';
    } // function


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
            $instance = new JavascriptTemplate();
        }
        return $instance;
    } // function


    /**
     * @internal
     */
    public function __construct()
    {
        parent::__construct();

        $this->setTemplateDir(self::getSourcePath());
        $this->addTemplateDir(Application::$FILE_ROOT.'javascript');
        $this->left_delimiter = '<!--{';
        $this->right_delimiter = '}-->';
    } // constructor


    /**
     * This function generates a new .js file from the existing files (if anything new happen to them or does not
     * exist). The name of the new file will be returned.
     *
     * @api
     *
     * @param array $files
     *
     * @return string|false false on error or nothing
     */
    public static function combine(array $files)
    {
        if (empty($files)) {
            return false;
        }

        $target = self::getTargetPath();
        $source = self::getSourcePath();

        // identify file combinations by hash
        $jsHash = md5(serialize($files));
        $targetfile = $target.$jsHash.'.js';

        // check if any source file was modified
        $needUpdate = false;
        if (file_exists($targetfile)) {
            $hashtime = filemtime($targetfile);
            foreach ($files as $file) {
                if (substr($file['name'], 0, 1) === '/') {

                    $filename = $file['name'];
                } elseif ($file['relative']) {

                    // relative to public/js
                    $filename = $target.$file['name'].'.js';
                } else {

                    // use javascript folder
                    $filename = $source.$file['name'].'.js.tpl';
                }

                if ($hashtime < filemtime($filename)) {
                    $needUpdate = true;
                    break;
                }
            }
        } else {

            // file does not exist, so we need an update anyway
            $needUpdate = true;
        }

        // we can abort if no update is required
        if ($needUpdate === false) {
            return $jsHash;
        }

        // make sure, that the target directory exists
        if (!is_dir($target)) {
            FileUtil::makedir($target);
        }

        // combine file contents
        $content = '';
        foreach ($files as $file) {

            try {
                if (substr($file['name'], 0, 1) === '/') {
                    if (strpos($file['name'], '.tpl') > 0) {
                        $nextcontent = JavascriptTemplate::getInstance()->fetch($file['name']);
                    } else {
                        $nextcontent = file_get_contents($file['name']);
                    }
                } elseif ($file['relative']) {
                    $nextcontent = file_get_contents($target.$file['name'].'.js');
                } else {
                    $nextcontent = JavascriptTemplate::getInstance()->fetch($source.$file['name'].'.js.tpl');
                }

                // do not double minify
                if (strpos($file['name'], '.min') > 0
                    || strpos($file['name'], '.pack') > 0
                    || !method_exists('\\JShrink\\Minifier', 'minify')
                ) {
                    $content .= $nextcontent."\n";
                } else {
                    $content .= \JShrink\Minifier::minify($nextcontent)."\n";
                }
            } catch (\ErrorException $exception) {
                Logger::getInstance()->exception($exception);
            }
        } // foreach file

        // write minified version
        $success = file_put_contents($targetfile, $content);

        // adjust file permissions for webserver
        if ($success) {
            chmod($targetfile, 0644);
        } // foreach scssFiles

        return $jsHash;
    } // function
}// class
