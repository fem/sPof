<?php
/**
 * This file is part of sPof.
 *
 * FIXME license
 *
 * @copyright 2003-2014 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
 * @link      http://spof.fem-net.de
 */

namespace FeM\sPof;

/**
 * Collection of functions related to strings.
 *
 * @package FeM\sPof
 * @author dangerground
 * @author deka
 * @since 1.0
 */
abstract class StringUtil
{
    /**
     * Generate a random string, with reduced danger of confusion.
     *
     * @api
     *
     * @param int $len
     * @return string
     */
    public static function randomString($len)
    {
        mt_srand();
        $possible = "ABCDEFGHJKLMNPRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789";
        $str = "";
        while (strlen($str) < $len) {
            $str .= substr($possible, (rand() % (strlen($possible))), 1);
        }
        return $str;
    } // function


    /**
     * Reduces String charset to a URL-friendly version.
     *
     * @api
     *
     * @param string $string
     * @return string
     */
    public static function reduce($string)
    {
        $string = str_replace('ä', 'ae', $string);
        $string = str_replace('ü', 'ue', $string);
        $string = str_replace('ö', 'oe', $string);
        $string = str_replace('ß', 'ss', $string);
        $string = str_replace('Ä', 'Ae', $string);
        $string = str_replace('Ü', 'Ue', $string);
        $string = str_replace('Ö', 'Oe', $string);
        $string = preg_replace('/[^a-zA-Z0-9_\\-.=\\+]/', '_', $string);
        $string = preg_replace('/_+/', '_', $string);
        return trim($string, '_');
    } // function


    /**
     * Like base64_decode but using . instead of / for decoding texts, makes it suitable for URLs.
     *
     * @api
     *
     * @param string $text
     * @return string
     */
    public static function pbase64Decode($text)
    {
        return base64_decode(str_replace('.', '/', $text));
    } // function


    /**
     * Like base64_encode but using . instead of / for encoding text, makes it suitable for URLs.
     *
     * @api
     *
     * @param $text
     * @return string
     */
    public static function pbase64Encode($text)
    {
        return str_replace('/', '.', base64_encode($text));
    } // function


    /**
     * Replace markup by HTML Formatting.
     *
     * @api
     *
     * @param string $string
     * @param bool $tohtml
     * @param bool $convertmarkup
     * @param bool $nl2br
     * @param bool $marklinks
     * @return string
     */
    public static function markup2html(
        $string,
        $tohtml = true,
        $convertmarkup = true,
        $nl2br = true,
        $marklinks = true
    ) {
        $out = $string;

        if ($tohtml) {
            $out = htmlentities($out, ENT_NOQUOTES);
        }

        if ($marklinks) {
            $domain = '(([a-z][a-z0-9\-]*\.)*[a-z][a-z0-9\-]*\.[a-z]{2,}';
            $search = [
                '/(?<!\[)(mailto:)?([a-z0-9._%-]+)@'.$domain.'([^\'\"><[:space:]\])]*))(?!\])/i',
                '/(?<!\[)http(s?):\/\/'.$domain.'([^\'\"><[:space:]\])]*))(?!\])/i',
                '/(?<!\[)([^\/])www\.'.$domain.'([^\'\"><[:space:]\|)]*))(?!\])/i',
            ];
            $replace = [
                '[[mailto:$2@$3|$2@$3]]',
                '[[http$1://$2|http$1://$2]]',
                '$1[[http://www.$2|http://www.$2]]',
            ];

            $out = preg_replace($search, $replace, $out);
        }

        if ($convertmarkup) {
            $search = [
                '#(?<![a-zA-Z0-9*])\*\*(.+)\*\*(?![a-zA-Z0-9*])#sU',  // **bold text**
                '#(?<![a-zA-Z0-9/])//(.+)//(?![a-zA-Z0-9/])#sU',      // //italic text//
                '#(?<![a-zA-Z0-9_])__(.+)__(?![a-zA-Z0-9_])#sU',      // __underlined text__
                '#(?<![a-zA-Z0-9_])\'\'(.+)\'\'(?![a-zA-Z0-9_])#sU',  // ''monospaced text''
                '#&lt;del&gt;(.+)&lt;/del&gt;#sU',                    // <del>strike through text</del>
                '#\[\[(.+)\|(.+)\]\]#U',                              // [[url|description text]]
                '#\[\[([^]]+?)\]\]#U',                                // [[url]]
                '#(?<![a-zA-Z0-9=])====(.+)====(?![a-zA-Z0-9=])#sU',  // ==== h4 headline ====
                '#(?<![a-zA-Z0-9=])===(.+)===(?![a-zA-Z0-9=])#sU',    // === h5 headline ===
                '#(?<![a-zA-Z0-9=])==(.+)==(?![a-zA-Z0-9=])#sU',      // == h6 headline ==
                ];

            $replace = [
                '<strong>$1</strong>',
                '<em>$1</em>',
                '<span style="text-decoration: underline;">$1</span>',
                '<span style="font-family: monospace;">$1</span>',
                '<del>$1</del>',
                '<a href="$1">$2</a>',
                '<a href="$1">$1</a>',
                '<h4>$1</h4>',
                '<h5>$1</h5>',
                '<h6>$1</h6>',
                ];

            $out = preg_replace($search, $replace, $out);

            // handle each line of text seperately
            $lines = explode("\n", $out);
            $types = [0 => 'u'];
            $level = 0;
            $linecount = count($lines);
            foreach ($lines as $linenr => &$line) {

                // indicated that the callback was used
                $linechange = false;

                // search for listing and replace with html listing
                $line = preg_replace_callback(
                    '#^(([[:space:]]{2})+)(\*|\-)(.+)$#',
                    function ($match) use (&$level, &$types, &$linechange) {

                        // get level from spaces
                        $thislevel = (int) (strlen($match[1]) / 2);

                        // start building the html content
                        $ret = '';

                        // increased level
                        if ($thislevel > $level) {

                            // only support one level increase
                            if ($thislevel !== $level + 1) {
                                return $match[0];
                            }

                            // update type(* ordered, - unordered) and html
                            $types[$thislevel] = ($match[3] === '*' ? 'u' : 'o');
                            $ret .= '<'.$types[$thislevel].'l class="contentlist"><li>';

                            // decreased level
                        } elseif ($thislevel < $level) {
                            $end = '';
                            for ($i = $level; $i > $thislevel; --$i) {
                                $end .= '</li></'.$types[$level].'l>';
                            }
                            $ret .= $end.'</li><li>';

                            // just a new leaf
                        } else {
                            $ret .= '</li><li>';
                        }

                        $ret .= trim($match[4]);
                        $linechange = true;
                        $level = $thislevel;
                        return $ret;
                    },
                    $line
                );

                // check (last line of text or listing ended) and close listings
                if (!$linechange || $linecount-1 == $linenr) {
                    $ends = '';
                    for ($i = $level; $i > 0; --$i) {
                        $ends .= '</li></'.$types[$i].'l>';
                    }
                    $level = 0;
                    $line = $line.$ends;
                }
            } // if close listing

            // rebuild text from lines
            $out = join("\n", $lines);
        } // if convertmarkup

        if ($nl2br === true) {
            $out = nl2br($out);
        } elseif ($nl2br === 2) {
            $out = preg_replace('#(^|[\n\r])(.+)([\n\r]|$)#', '<p>$2</p>', $out);
        }

        return $out;
    } // function
}// class
