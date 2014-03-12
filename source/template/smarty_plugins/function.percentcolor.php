<?php
/**
 * This file is part of sPof.
 *
 * FIXME license
 *
 * @copyright 2003-2014 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
 * @link      http://spof.fem-net.de
 */

/**
 * Calculate a color based on current percentage of max.
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author deka
 * @author pegro
 * @since 1.0
 *
 * @api
 *
 * @param array $params
 * @param Smarty $smarty (reference)
 *
 * @return string
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
function smarty_function_percentcolor($params, &$smarty)
{
    $cur = (isset($params['current']) && preg_match('/^[0-9]+$/', $params['current']))?$params['current']:0;
    $min = (isset($params['minimum']) && preg_match('/^[0-9]+$/', $params['minimum']))?$params['minimum'] + 0:0;
    $max = (isset($params['maximum']) && preg_match('/^[0-9]+$/', $params['maximum']) && $params['maximum'] > 0)
        ? $params['maximum']
        : 1;
    if ($cur > $max) {
        $cur = $max;
    }
    if ($cur < $min) {
        $cur = $min;
    }
    $value = dechex(round(strval(255 - 238 * ($cur - $min) / ($max - $min))));

    if (!isset($params['color'])) {
        $params['color'] = '';
    }
    switch ($params['color']) {
        case 'green':
            return "33ee".$value;

        case 'blue':
            return "33".$value."ff";

        case 'red':
        default:
            return "ff".$value."00";
    }
} // function
