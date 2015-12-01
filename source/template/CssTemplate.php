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
use FeM\sPof\Cache;
use FeM\sPof\Config;
use FeM\sPof\Logger;
use FeM\sPof\FileUtil;

/**
 * This class handles the CssTemplate generation. Its main method is update() which checks the timestamps for required
 * updates and then generates all relevant css files.
 *
 * @package FeM\sPof\template
 * @author dangerground
 * @since 1.0
 */
class CssTemplate
{

    /**
     * Default config.
     *
     * @api
     *
     * @var array
     */
    private static $defaultConfig = [
        'style' => \SassRenderer::STYLE_NESTED, // CSS output style, might be: nested / compressed / compact / expanded
        'syntax' => \SassFile::SCSS,
        'cache' => false,
        'debug' => true,
        'file_perms' => 0644,
        'check_file_level' => false,
    ];


    /**
     * Get path to which the file should be put to.
     *
     * @internal
     *
     * @return string
     */
    private static function getTargetPath()
    {
        return Application::$WEB_ROOT.'css/';
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
        return dirname(Application::$WEB_ROOT).'/stylesheet/';
    } // function


    /**
     * This function generates new .css files from the .scss files in the stylesheet folder under the condition, that
     * the .scss files are newer or possible dependencies are updated. Files that begin with underscore (_) are not
     * transformed and are only used for defining parts of rules.
     *
     * @api
     */
    public static function update()
    {
        $sourcePath = self::getSourcePath();
        $targetPath = self::getTargetPath();

        if (!is_dir($targetPath)) {
            FileUtil::makedir($targetPath);
        }

        // check if source has changed
        $updated = Cache::fetch('style.update');
        if ($updated !== false && $updated > filemtime($sourcePath)) {

            // check all files in the source folder for modifications
            if (Config::getDetail('stylesheet', 'check_file_level', self::$defaultConfig)) {
                $needUpdate = false;

                $dir = new \DirectoryIterator($sourcePath);
                foreach ($dir as $file) {
                    /** @var \DirectoryIterator $file */

                    $filename = $file->getFilename();

                    // ignore dirs
                    if ($file->isDot() || $file->isDir() || strpos($filename, '.#') === 0) {
                        continue;
                    }

                    if ($updated < filemtime($sourcePath.$filename)) {
                        $needUpdate = true;
                        break;
                    }
                } // foreach dir

                if (!$needUpdate) {
                    return;
                }
            } else {
                return;
            }
        }

        $dir = new \DirectoryIterator($sourcePath);

        // remember last update (begin with this file as dependency)
        $lastDependencyUpdate = filemtime(__FILE__);

        // list of files to generate
        $scssFiles = [];

        // first step: generate list of files and get last updated dependency file
        foreach ($dir as $file) {
            /** @var \DirectoryIterator $file */

            $filename = $file->getFilename();

            // ignore dirs
            if ($file->isDot() || $file->isDir() || strpos($filename, '.#') === 0) {
                continue;
            }

            if (strpos($filename, '_') === 0) {
                $lastDependencyUpdate = max($lastDependencyUpdate, filemtime($sourcePath.$filename));
            } else {
                $scssFiles[] = $filename;
            }
        } // foreach dir

        foreach ($scssFiles as $filename) {

            // does target exist? does it have an older timestamp?
            $savefile = $targetPath.str_replace('.scss', '.css', $filename);
            if (file_exists($savefile)) {
                $modified = filemtime($savefile);
                if (filemtime($sourcePath.$filename) <= $modified && $lastDependencyUpdate <= $modified) {
                    continue;
                }
            }

            // save and set file permissions

            /*require_once "vendor/leafo/scssphp/scss.inc.php";
            $scss = new \scssc();
            $scss->addImportPath(function ($path) {
                if (!file_exists('stylesheet/'.$path)) {
                    return null;
                }
                return 'stylesheet/'.$path;
            });
*/

            try {
                // will import `stylesheets/vanilla.css'
  //              file_put_contents($savefile, $scss->compile('@import "'.$filename.'"'));


                file_put_contents($savefile, self::getParser()->toCss(file_get_contents($sourcePath.$filename), false));
                chmod($savefile, Config::getDetail('stylesheet', 'file_perms', self::$defaultConfig));
            } catch (\Exception $exception) {
                Logger::getInstance()->exception($exception);
            }
        } // foreach scssFiles

        Cache::store('style.update', time());

    } // function


    /**
     * Get Instance of the source parser with its default config.
     *
     * @internal
     *
     * @return \SassParser
     */
    private static function getParser()
    {
        static $parser;
        if (!isset($parser)) {
            $options = Config::get('stylesheet', self::$defaultConfig);
            $options['load_paths'] = [self::getSourcePath()];

            $parser = new \SassParser($options);
        }
        return $parser;
    } // function


    /**
     * This function generates a new .css file from the existing files (if anything new happen to them or does not
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
        $cssHash = md5(serialize($files));
        $targetfile = $target.$cssHash.'.css';

        // check if any source file was modified
        $needUpdate = false;
        if (file_exists($targetfile)) {
            $hashtime = filemtime($targetfile);
            foreach ($files as $file) {
                if ($hashtime < filemtime($file['name']) || !file_exists($file['name'])) {
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
            return $cssHash;
        }

        // combine file contents
        $handle = fopen($targetfile, 'w+');
        foreach ($files as $file) {
            $content = file_get_contents($file['name']);
            if ($file['fixpaths']) {
                preg_match_all('/url\(([^)]+)\)/', $content, $matches, PREG_SET_ORDER);
                $replaces = [];
                $copy = [];
                foreach ($matches as $match) {
                    if (strpos($match[1], 'data') === 0) {
                        continue;
                    }

                    $filename = 'gen__'.md5($file['name'].'-'.$match[1]).preg_replace('/^[^.]+\.(.+)$/', '.$1', $match[1]);
                    $replaces[$match[0]] = 'url(../img/'.$filename.')';
                    $copy[dirname($file['name']).'/'.$match[1]] = Application::$WEB_ROOT.'img/'.$filename;
                }

                // replace usage in stylesheet and copy file to be accessible via web
                $content = str_replace(array_keys($replaces), $replaces, $content);
                foreach ($copy as $source => $target) {
                    try {
                        copy($source, $target);
                    } catch(\ErrorException $e) {
                        Logger::getInstance()->exception($e);
                    }
               }
            }
            fwrite($handle, self::minify($content));
        } // foreach file
        fclose($handle);

        // adjust file permissions for webserver
        chmod($targetfile, Config::getDetail('stylesheet', 'file_perms', self::$defaultConfig));

        return $cssHash;
    } // function


    /**
     * Minify a CSS String.
     *
     * @param string $content
     * @return string
     */
    public static function minify($content) {
        // remove comments
        $content = preg_replace('#/\*.+\*/#sU', '', $content);

        // remove whitespace
        return preg_replace('#\s+#', ' ', $content);
    } // function
}// class
