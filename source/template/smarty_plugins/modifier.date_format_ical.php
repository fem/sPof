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
 * Format a date in a ical compatible way
 *
 * @package FeM\sPof\template\smartyPlugins
 * @author mbyr
 * @since 1.0
 *
 * @internal
 *
 * @param string $string
 * @param string $default_date (optional)
 * @return string
 */
function smarty_modifier_date_format_ical($string, $default_date = '')
{
    require_once SMARTY_PLUGINS_DIR.'shared.make_timestamp.php';

    if ($string != '') {
        $timestamp = smarty_make_timestamp($string);
    } elseif ($default_date != '') {
        $timestamp = smarty_make_timestamp($default_date);
    } else {
        return '';
    }

    $currentLocale = setlocale(LC_TIME, null);
    setlocale(LC_TIME, "C");
    $return = date('Ymd\THis', $timestamp);
    setlocale(LC_TIME, $currentLocale);
    return $return;
} // function
