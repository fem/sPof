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
 * Collection of functions related to files.
 *
 * @package FeM\sPof
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
abstract class FileUtil
{
    /**
     * Create a new directory (and all others in the path).
     *
     * @api
     *
     * @param string $path
     * @param int $permission
     * @return bool
     */
    public static function makedir($path, $permission = 0770)
    {
        if (file_exists($path)) {
            return true;
        }
        $umask = umask(0);
        try {
            $success = mkdir($path, $permission, true);
            umask($umask);
        } catch (\ErrorException $e) {
            $success = false;
        }

        if (!$success) {
            user_error(_s('can\'t create folder "%s".', $path));
        }
        return $success;
    } // function makedir


    /**
     * Create a new file in the database from a HTTP request.
     *
     * @api
     *
     * @param array $fileobject subarray of $_FILES
     * @param string $alternateFile (optional) alternate file (e.g. if you resize images before saving)
     * @param int $index (optional) on multifile upload map to this fileindex
     *
     * @return array
     */
    public static function createFromHttp($fileobject, $alternateFile = null, $index = null)
    {
        $file = [];

        // on multi file upload, map to specific object
        if ($index !== null) {
            $fileobject['tmp_name'] = $fileobject['tmp_name'][$index];
            $fileobject['name'] = $fileobject['name'][$index];
            $fileobject['type'] = $fileobject['type'][$index];
        }

        if ($alternateFile !== null) {
            $fileobject['tmp_name'] = $alternateFile;
        }

        // get mimetype
        $finfo = new \finfo(FILEINFO_MIME);
        if (!$finfo) {
            $file['mimetype'] = $finfo->file($fileobject['tmp_name']);
        } else {
            $file['mimetype'] = $fileobject['type'];
        }

        // upload file
        $file['content_oid'] = model\DBConnection::getInstance()->pgsqlLOBCreate();
        $stream = model\DBConnection::getInstance()->pgsqlLOBOpen($file['content_oid'], 'w');
        $local = fopen($fileobject['tmp_name'], 'rb');
        stream_copy_to_stream($local, $stream);
        $stream = null;
        $local = null;

        // get misc info
        $file['hash'] = md5_file($fileobject['tmp_name']);
        $file['size'] = filesize($fileobject['tmp_name']);
        $file['filename'] = $fileobject['name'];

        return $file;
    } // function


    /**
     * Make sure that filenames for images are valid.
     *
     * @api
     *
     * @throws exception\ControllerException
     *
     * @param string $filename
     */
    public static function requireValidImageFilename($filename)
    {
        $pathinfo = pathinfo($filename);
        if (empty($pathinfo['filename'])) {
            throw new exception\ControllerException(_s('Es wurde keine Datei zum Hochladen ausgewählt.'));
        }

        $extension = strtolower($pathinfo['extension']);
        if ($extension != 'jpeg' && $extension != 'jpg' && $extension != 'png') {
            throw new exception\ControllerException(_s('Die hochgeladene Vorschaudatei ist kein JPEG- oder PNG-Bild!'));
        }
    } // function
}// class
