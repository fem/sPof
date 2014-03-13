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

/**
 * This class handles routing. It takes a YAML file as source and generates htaccess rules to get from a url to the
 * correct module / controller / view. It's also responsible to generate the URLs. You may use some preset htaccess
 * rules which are prepend to the generated file.
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
class Router
{
    /**
     * Get target location file.
     *
     * @internal
     *
     * @return string
     */
    private static function getTargetFile()
    {
        return Application::$WEB_ROOT.'/.htaccess';
    } // function


    /**
     * Get routing source file.
     *
     * @internal
     *
     * @return string
     */
    private static function getSourceFile()
    {
        return Application::$FILE_ROOT.'config/routes.yml';
    } // function


    /**
     * Get htaccess preset/prefix file.
     *
     * @return string
     */
    private static function getPresetFile()
    {
        return Application::$FILE_ROOT.'config/htaccess';
    } // function


    /**
     * This function generates the htaccess ruleset based on the preset and the generated routes defined in the source
     * file. Generation is only done if relevant files got changed.
     *
     * @internal
     */
    public static function updateHtaccess()
    {
        $target = self::getTargetFile();
        $preset = self::getPresetFile();

        // check if rulesset is outdated, before updating it
        if (file_exists($target)) {
            $target_time = filemtime($target);
            if (filemtime($preset) < $target_time && filemtime(__FILE__) < $target_time) {
                return;
            }
        }

        $routes = self::getRoutes();

        // if parse error occurred, stop here
        if (empty($routes)) {
            return;
        }

        $rules = fopen($target, 'w+');

        ob_start();

        /** @noinspection PhpIncludeInspection */
        require $preset;
        fwrite($rules, ob_get_clean());
        fclose($rules);

        chmod($target, 0644);
    } // function


    /**
     * This function generates the htaccess ruleset based on the preset and the generated routes defined in the source
     * file. Generation is only done if relevant files got changed.
     *
     * @internal
     */
    public static function updateRules()
    {
        $target = self::getTargetFile();
        $source = self::getSourceFile();
        $preset = self::getPresetFile();

        // check if rulesset is outdated, before updating it
        if (file_exists($target)) {
            $target_time = filemtime($target);
            if (filemtime($source) < $target_time
                && filemtime($preset) < $target_time
                && filemtime(__FILE__) < $target_time
            ) {
                return;
            }
        }

        $routes = self::getRoutes();

        // if parse error occurred, stop here
        if (empty($routes)) {
            return;
        }

        $rules = fopen($target, 'w+');

        ob_start();

        /** @noinspection PhpIncludeInspection */
        require $preset;
        fwrite($rules, ob_get_clean());

        // write each rule
        $unfolded = self::expandAndSort($routes);
        foreach ($unfolded as $route) {
            fwrite(
                $rules,
                'RewriteRule ^'.self::getRegexPattern($route['pattern']).'$ '
                .self::getReplaceUrl($route['pattern'], $route['static']).'&%{QUERY_STRING} [L,QSA]'."\n"
            );
        }

        // write 404 rule last
        fwrite(
            $rules,
            'RewriteRule ^.*$ '
            .'index.php?module=errors&show=show404&%{QUERY_STRING} [L,QSA]'."\n"
        );
        fclose($rules);

        chmod($target, 0644);
    } // function


    /**
     * Takes a URL-Pattern and modify it, so that it can be used by htaccess to parse the current url and redirect to
     * the URL generated by getReplaceUrl().
     *
     * @internal
     *
     * @param string $pattern
     *
     * @return string
     */
    private static function getRegexPattern($pattern)
    {
        $pattern = preg_replace('/<[^>]+>$/', '(.*)', $pattern);
        $pattern = preg_replace('/<[^>]+>(.)/', '([^$1]*)$1', $pattern);
        $pattern = trim($pattern, '/');
        return $pattern;
    } // function


    /**
     * Takes a url pattern and predefined arguments and builds a replacement url for htaccess mod_rewrite rules.
     *
     * @internal
     *
     * @param string $pattern url pattern to translate
     * @param array   $arguments (optional) predefined arguments (default arguments: like module / action / show)
     *
     * @return string the target url
     */
    private static function getReplaceUrl($pattern, array $arguments = [])
    {
        $new = [];
        foreach ($arguments as $param => $value) {
            $new[] = $param.'='.$value;
        }

        $rule = 0;
        if (preg_match_all('/<([^>]+)>/', $pattern, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                ++$rule;
                $new[] = $match[1].'=$'.$rule;
            }
        }

        return 'index.php?'.implode('&', $new);
    } // function


    /**
     * This function returns a url based on the route name and the arguments which are filled in the url pattern.
     *
     * @api
     *
     * @throws \InvalidArgumentException
     *
     * @param string $name route name
     * @param array $arguments (optional) arguments which replace the placeholder in the url pattern
     *
     * @return string
     */
    public static function reverse($name, array $arguments = [])
    {
        static $routes;
        if ($routes === null) {
            $routes = self::getRoutes();
        }

        // check if the routes.yml was parsed correctly, if not, stop debugspam here
        if (empty($routes)) {
            return '';
        }

        // if we have no name, so throw arguments of the
        if (empty($name)) {
            Logger::getInstance()->error(
                'Missing URL Name, just got params: '.var_export($arguments, true)
            );
        }

        // check for existing name
        if (!isset($routes[$name])) {
            Logger::getInstance()->error(
                'Could not find URL with name: "'.$name.'" and params in '.var_export($arguments, true)
            );
            return '';
        }

        $pattern = $routes[$name]['pattern'];
        $suffix = (isset($routes[$name]['optional_suffix']) ? $routes[$name]['optional_suffix'] : null);
        $prefix = (isset($routes[$name]['optional_prefix']) ? $routes[$name]['optional_prefix'] : null);
        $patternOptional = rtrim($prefix.$pattern.$suffix, '/');
        $patternSufOptional = rtrim($pattern.$suffix, '/');
        $patternPreOptional = rtrim($prefix.$pattern, '/');
        $pattern = rtrim($pattern, '/');

        // replace placeholder with their value from arguments, use optional params as base, as it contains all params
        if (preg_match_all('/<([^>]+)>/S', $patternOptional, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {

                // skip non existing params
                if (!isset($arguments[$match[1]]) || $arguments[$match[1]] === null) {
                    continue;
                }
                $pattern = str_replace('<'.$match[1].'>', StringUtil::reduce($arguments[$match[1]]), $pattern);
                $patternOptional = str_replace(
                    '<'.$match[1].'>',
                    StringUtil::reduce($arguments[$match[1]]),
                    $patternOptional
                );
                $patternSufOptional = str_replace(
                    '<'.$match[1].'>',
                    StringUtil::reduce($arguments[$match[1]]),
                    $patternSufOptional
                );
                $patternPreOptional = str_replace(
                    '<'.$match[1].'>',
                    StringUtil::reduce($arguments[$match[1]]),
                    $patternPreOptional
                );
            }
        }

        // check for remaining unresolved params
        if (strpos($pattern, '<')) {
            Logger::getInstance()->error(
                'Could not resolve all params in "'.$name.'": "'.$pattern.'". Arguments='.var_export($arguments, true)
            );
        }

        // check if all optional params are resolved, if not -> return normal path
        if (strpos($patternOptional, '<') === false) {

            // optional params are resolved, so return full path
            return $patternOptional.(isset($arguments['_anchor']) ? '#'.$arguments['_anchor'] : '');
        } elseif (strpos($patternPreOptional, '<') === false) {

            return $patternPreOptional.(isset($arguments['_anchor']) ? '#'.$arguments['_anchor'] : '');
        } elseif (strpos($patternSufOptional, '<') === false) {

            // optional params are resolved, so return full path
            return $patternSufOptional.(isset($arguments['_anchor']) ? '#'.$arguments['_anchor'] : '');
        } else {

            // join parts together
            return $pattern.(isset($arguments['_anchor']) ? '#'.$arguments['_anchor'] : '');
        }
    } // function


    /**
     * Alternative implementation to the generation of htaccess rules. Try to resolve the given path by our patterns.
     *
     * @internal
     *
     * @param $path
     *
     * @return bool
     */
    public static function resolve($path)
    {
        preg_match_all('#([a-z]+):([0-9a-z]+)#iU', $path, $optionals, PREG_SET_ORDER);
        foreach ($optionals as $optional) {
            $path = str_replace($optional[0], '', $path);
            $_GET[$optional[1]] = $optional[2];
        }

        $path = trim($path, '/');
        $routes = self::getRoutes();
        $unfolded = self::expandAndSort($routes);
        foreach ($unfolded as $route) {
            $route_pattern = $route['pattern'];
            $route_pattern = '%^'.preg_replace('#<[^>/]+?>#', '(.*)', $route_pattern).'$%siU';
            if (preg_match($route_pattern, $path, $matches)) {
                preg_match(
                    '%^'.preg_replace('#<[^>/]+?>#', '<(.*)>', $route['pattern']).'$%siU',
                    $route['pattern'],
                    $params
                );
                foreach ($route['static'] as $key => $value) {
                    $_GET[$key] = $value;
                }
                for ($i = 1; $i < count($params); $i++) {
                    $_GET[$params[$i]] = $matches[$i];
                }
                return true;
            }
        }

        Cache::delete('unfolded_routes');
        return false;
    } // function


    /**
     * Loads the routes from source file. Depends on php yaml extension of sfYaml to load the file and return it as
     * array.
     *
     * @internal
     *
     * @return array with routes
     */
    public static function getRoutes()
    {
        static $flat;
        if (!empty($flat)) {
            return $flat;
        }

        try {
            $ret = yaml_parse_file(self::getSourceFile());
        } catch (\ErrorException $e) {
            Logger::getInstance()->error('Syntax error in file "config/routes.yml": '.$e->getMessage());
            $ret = [];
        }

        // convert to "flat" routes
        $flat = [];
        foreach ($ret as $group => $route) {
            if (isset($route['subroutes'])) {
                foreach ($route['subroutes'] as $name => $subroute) {
                    if (isset($subroute['static'])) {
                        $flat[$group.'_'.$name]['static'] = $subroute['static'];
                    }
                    $flat[$group.'_'.$name]['pattern'] = $route['pattern'].$subroute['pattern'];
                    $flat[$group.'_'.$name]['static']['module'] = $route['module'];

                    if (!empty($subroute['show'])) {
                        $flat[$group.'_'.$name]['static']['show'] = $subroute['show'];
                    }
                    if (!empty($subroute['action'])) {
                        $flat[$group.'_'.$name]['static']['action'] = $subroute['action'];
                    }

                    if (isset($subroute['optional_suffix'])) {
                        $flat[$group.'_'.$name]['optional_suffix'] = $subroute['optional_suffix'];
                    }
                    if (isset($subroute['optional_prefix'])) {
                        $flat[$group.'_'.$name]['optional_prefix'] = $subroute['optional_prefix'];
                    }
                    if (isset($subroute['skiptest'])) {
                        $flat[$group.'_'.$name]['skiptest'] = $subroute['skiptest'];
                    }
                } // foreach subroute
            }

            if (isset($route['show']) || isset($route['action'])) {
                $flat[$group] = [
                    'pattern' => $route['pattern'],
                    'static' => [
                        'module' => $route['module'],
                    ]
                ];
                if (!empty($route['show'])) {
                    $flat[$group]['static']['show'] = $route['show'];
                }
                if (!empty($route['action'])) {
                    $flat[$group]['static']['action'] = $route['action'];
                }
                if (!empty($route['optional_suffix'])) {
                    $flat[$group]['optional_suffix'] = $route['optional_suffix'];
                }
                if (!empty($route['optional_prefix'])) {
                    $flat[$group]['optional_prefix'] = $route['optional_prefix'];
                }
            } // if show
        } // foreach route

        return $flat;
    } // function


    /**
     * Get current module name, returns false if no specific module is given.
     *
     * @api
     *
     * @return string|false
     */
    public static function getModule()
    {
        return ucfirst(isset($_GET['module']) ? $_GET['module'] : false);
    } // function


    /**
     * Returns the current action (for controller).
     *
     * @api
     *
     * @param bool $default
     *
     * @return string|false false if no action is given
     */
    public static function getAction($default = false)
    {
        return isset($_GET['action']) ? $_GET['action'] : $default;
    } // function


    /**
     * Returns the current show (for view).
     *
     * @api
     *
     * @param bool $default
     *
     * @return string|false false if no action is given
     */
    public static function getShow($default = false)
    {
        return isset($_GET['show']) ? $_GET['show'] : $default;
    } // function


    /**
     * This function just returns the param. The use of the function is to be able, to check usage of routes in tests.
     * Nothing more.
     *
     * @api
     *
     * @param string $route
     *
     * @return string
     */
    public static function delegate($route)
    {
        return $route;
    } // function


    /**
     * Redirect to route.
     *
     * @internal
     *
     * @param string $route
     * @param array $arguments
     */
    public static function redirect($route, array $arguments)
    {
        $server = Config::get('server');
        self::urlRedirect($server['url'] . $server['path'] . self::reverse($route, $arguments));
    } // function


    /**
     * Redirect to URL and stop further execution.
     *
     * @internal
     *
     * @param string $url
     */
    public static function urlRedirect($url)
    {
        Logger::getInstance()->stackData();
        session_write_close();
        header('Location: ' . $url);

        exit;
    } // function


    /**
     * Expand optional prefix&suffix routes and return a priorized queue.
     *
     * @internal
     *
     * @param $routes
     *
     * @return \SplPriorityQueue
     */
    private static function expandAndSort($routes)
    {
        $unfolded = new \SplPriorityQueue();
        foreach ($routes as $route) {
            $static = &$route['static'];
            // has optional prefix & suffix
            if (isset($route['optional_prefix']) && isset($route['optional_suffix'])) {
                $pattern = rtrim($route['optional_prefix'] . $route['pattern'] . $route['optional_suffix'], '/');
                $unfolded->insert(
                    ['pattern' => $pattern, 'static' => $static],
                    strlen(preg_replace('/<[^>]+>/', '###', $pattern))
                );
            } // if prefix & suffix

            // has optional suffix
            if (isset($route['optional_suffix'])) {
                $pattern = rtrim($route['pattern'] . $route['optional_suffix'], '/');
                $unfolded->insert(
                    ['pattern' => $pattern, 'static' => $static],
                    strlen(preg_replace('/<[^>]+>/', '###', $pattern))
                );
            } // if suffix

            // has optional prefix
            if (isset($route['optional_prefix'])) {
                $pattern = rtrim($route['optional_prefix'] . $route['pattern'], '/');
                $unfolded->insert(
                    ['pattern' => $pattern, 'static' => $static],
                    strlen(preg_replace('/<[^>]+>/', '###', $pattern))
                );
            } // if prefix

            // would result in parsing problems if we have a trailing slash (route couldn't get found)
            $route['pattern'] = rtrim($route['pattern'], '/');

            // always add route without optional prefix & suffix
            $unfolded->insert(
                ['pattern' => $route['pattern'], 'static' => $static],
                strlen(preg_replace('/<[^>]+>/', '###', $route['pattern']))
            );
        } // end foreach route

        return $unfolded;
    } // function
}// class
