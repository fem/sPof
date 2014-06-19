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

namespace FeM\sPof\notification;

use FeM\sPof\Config;
use FeM\sPof\Logger;

/**
 * Helper class to end emails with some predefined (and project specific) headers.
 *
 * @package FeM\sPof\notification
 * @author dangerground
 * @since 1.0
 */
abstract class MailUtil
{

    /**
     * Default config.
     *
     * @api
     *
     * @var array
     */
    private static $defaultConfig = [
        'subject_prefix' => '',
    ];

    /**
     * Send mails.
     *
     * @api
     *
     * @param string $email
     * @param string $title email subject
     * @param string $message email content
     * @param array $header (optional)
     *
     * @return bool
     */
    public static function send($email, $title, $message, array $header = [])
    {
        // Mime version header
        if (empty($header['MIME-Version'])) {
            $header['MIME-Version'] = '1.0';
        }

        // content type header
        if (empty($header['Content-type'])) {
            $header['Content-type'] = 'text/plain; charset=UTF-8';
        }

        // Message Id header
        if (empty($header['Message-ID'])) {
            $header['Message-ID'] = '<'.time().rand(1, 1000).'@'.$_SERVER['SERVER_NAME'].'>';
        }

        // concatenate prefix string with the subject
        $config = Config::get('email', self::$defaultConfig);
        $title = $config['subject_prefix'].$title;

        // From header
        if (empty($header['From']) && isset($config['support'])) {
            $header['From'] = $config['support'];
        }

        // trim line length and remove spaces around line breaks
        $message = wordwrap($message, 76);
        $msgs = explode("\n", $message);
        foreach ($msgs as & $msg) {
            $msg = trim($msg);
        }
        $message = implode("\n", $msgs);

        array_walk(
            $header,
            function (&$value, $key) {
                $value = $key.': '. ($key != 'Content-type' ? self::encodeMailAdress($value) : $value);
            }
        );

        Logger::getInstance()->info(
            'email an: "'.$email.'" mit titel: "'.$title.'" und nachricht "'.$message.'" wurde mit "'
            .var_export($header, true).'" als headern gesendet'
        );

        return mail(self::encodeMailAdress($email), mb_encode_mimeheader($title), $message, implode("\n", $header));
        //return true;
    } // function


    /**
     * Encode a email-adress name (not the mail itself, just the name).
     *
     * @api
     *
     * @param $adress
     *
     * @return string
     */
    public static function encodeMailAdress($adress)
    {
        $to = explode('<', $adress);
        for ($i = 0; $i < count($to); $i += 2) {
            $to[$i] = mb_encode_mimeheader($to[$i]);
        }
        $adress = implode('<', $to);
        return $adress;
    } // function
}// class
