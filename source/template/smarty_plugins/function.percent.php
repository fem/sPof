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
 * Calculate current percentage.
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
function smarty_function_percent($params, &$smarty)
{
    $cur = (preg_match('/^[0-9]+$/', $params['current']))?$params['current'] : 0;
    $max = (preg_match('/^[0-9]+$/', $params['maximum']) && $params['maximum'] > 0) ? $params['maximum'] : 1;
    if ($cur > $max) {
        $cur = $max;
    }
    return ceil(100 * $cur / $max);
} // function
