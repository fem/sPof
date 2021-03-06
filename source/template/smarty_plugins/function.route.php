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

/**
 * Wrapper for @see Router::reverse(). Additional params are _fullurl and _https, for links with https-protocol and
 * absolute urls. Define the route name with _name and everything else is used as arguments, you may also specify all
 * arguments at once using the 'arguments' param.
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author dangerground
 * @since 1.0
 *
 * @api
 *
 * @throws FeM\sPof\exception\SmartyTemplateException
 *
 * @param array $params
 * @param Smarty $smarty
 *
 * @return string
 */
function smarty_function_route($params, &$smarty)
{
    // get array elements as single params
    if (isset($params['arguments']) && is_array($params['arguments'])) {
        $params = array_merge($params['arguments'], $params);
        unset($params['arguments']);
    }

    $arguments = $params;
    unset($arguments['_name']);
    $fullurl = (isset($arguments['_fullurl']) ? $arguments['_fullurl'] : false);
    unset($arguments['_fullurl']);
    $https = isset($arguments['_https']) ? $arguments['_https'] : \FeM\sPof\Request::isSecure();
    unset($arguments['_https']);

    try {
        $url = \FeM\sPof\Router::reverse($params['_name'], $arguments, $fullurl);
        return ($fullurl && !$https) ? preg_replace('#^https#', 'http', $url) : $url;
    } catch (\InvalidArgumentException $e) {
        throw new \FeM\sPof\exception\SmartyTemplateException(
            __FUNCTION__.': '.$e->getMessage(),
            $smarty->getTemplateDir()[0].$smarty->template_resource,
            $e
        );
    }
} // function
