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

namespace FeM\sPof\view;

use FeM\sPof\Application;
use FeM\sPof\Config;
use FeM\sPof\model\DBConnection;
use FeM\sPof\Session;
use FeM\sPof\FileUtil;
use FeM\sPof\Request;

/**
 * Handle requests for files via rpc.
 *
 * @package FeM\sPof\view
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
class RpcFileView extends \FeM\sPof\view\AbstractRawView
{
    /**
     * Shall file be processed with a function.
     *
     * @internal
     *
     * @var bool
     */
    protected $processing = false;

    /**
     * Do not track stats.
     *
     * @internal
     *
     * @var bool
     */
    protected $stats_disable = false;

    /**
     * File which should be served.
     *
     * @internal
     *
     * @var bool
     */
    protected $path_sendfile = false;

    /**
     * file sid
     *
     * @internal
     *
     * @var string
     */
    protected $sid;


    /**
     * initialize.
     *
     * @internal
     *
     * @var bool
     */
    public function __construct()
    {
        $this->sid = Request::getStrParam('sid');
        if (empty($this->sid)) {
            self::sendNotFound();
        }

        $this->executeShow();
    } // constructor


    /**
     * Download a file.
     *
     * @internal
     */
    public function download()
    {

        // get and check sid
        if (empty($this->sid) || strlen($this->sid) > 32) {
            self::sendNotFound();
        }

        $db = DBConnection::getInstance();
        $db->beginTransaction();

        // get file details
        $stmt = $db->prepare(
            "
            SELECT
                id,
                content_oid,
                mimetype,
                size,
                name,
                to_char(modify,'Dy, DD Mon YYYY HH24:MI:SS TZ') AS modify
            FROM tbl_file
            WHERE
                sid=:sid
                AND visible IS TRUE
                AND disabled IS FALSE
            "
        );
        $stmt->bindParam('sid', $this->sid, \PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 0) {
            self::sendNotFound();
        }
        $file = $stmt->fetch(\PDO::FETCH_ASSOC);

        // set path to unprocessed cache file
        $path_cachefile = sprintf(Application::$CACHE_ROOT.'files/%s/%s.file', $this->sid[0], $this->sid);


        // load file from database if cache file doesn't exist
        $handle = false;
        if (file_exists($path_cachefile) === false) {
            // open db handle if no cached file available
            $handle = $db->pgsqlLOBOpen($file['content_oid'], 'r');

            if (feof($handle)) {
                self::sendNotFound();
            }

            // try to create cache
            if (!FileUtil::makedir(dirname($path_cachefile))) {
                error_log('cache'.$path_cachefile.' not writeable');
            } else {
                // create cache file
                $fcache = fopen($path_cachefile, 'w');
                stream_copy_to_stream($handle, $fcache);
                fclose($fcache);

                // close db handle
                fclose($handle);
                $handle = false;
            }
        }

        // check wether path_sendfile is set explicitly
        if (!empty($this->path_sendfile)) {
            // update stat data if exists
            if (is_readable($this->path_sendfile)) {
                $stat = stat($this->path_sendfile);
                $file['modify'] = $stat['mtime'];
                $file['size'] = $stat['size'];
            } elseif (isset($this->processing) && method_exists($this, 'processCachefile')) { // check for processing
                $file['cachefile'] = $path_cachefile;
                $this->processCachefile($file);
            } else {
                self::sendNotFound();
            }
        } else {
            $this->path_sendfile = $path_cachefile;
        }

        if (!is_readable($this->path_sendfile) && !is_resource($handle)) {
            self::sendNotFound();
        }


        // check wether stats_disable is set
        if ($this->stats_disable === false) {
            // insert statistics
            $stmt = $db->prepare(
                "
                    INSERT INTO tbl_statistic_file (user_id, ip, file_id)
                    VALUES (:user_id, :ip, :file_id)
                "
            );
            if (is_null(Session::getUserId())) {
                $stmt->bindValue('user_id', null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue('user_id', Session::getUserId(), \PDO::PARAM_INT);
            }
            $stmt->bindValue('ip', Request::getIp(), \PDO::PARAM_STR);
            $stmt->bindValue('file_id', $file['id'], \PDO::PARAM_INT);
            $stmt->execute();
        }

        $db->commit();

        // there is nothing to write, tell the session it is no longer needed (and thus no longer blocking output
        // flushing)
        session_write_close();

        // check if http_range is sent by browser
        if (isset($_SERVER['HTTP_RANGE'])) {
            $unit = explode('=', $_SERVER['HTTP_RANGE'])[0];

            if ($unit == 'bytes') {
                $range_list = explode('=', $_SERVER['HTTP_RANGE'], 2)[1];

                // multiple ranges could be specified at the same time, but for simplicity only serve the first range
                // http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                $ranges = explode(',', $range_list);

                foreach ($ranges as $range) {
                    $seek_start = explode('-', $range)[0];
                    $seek_end = explode('-', $range)[1];

                    // set valid and in range for seek end
                    if (empty($seek_end)) {
                        $seek_end = $file['size'] - 1;
                    } else {
                        $seek_end = min(abs($seek_end), $file['size'] - 1);
                    }

                    // set valid and in range for seek start
                    if (empty($seek_start) || $seek_end < abs($seek_start)) {
                        $seek_start = 0;
                    } else {
                        $seek_start = max(abs($seek_start), 0);
                    }

                    if (!is_resource($handle)) {
                        $handle = fopen($path_cachefile, 'rb');
                    }

                    // seek to start of missing part
                    if (($seek_start > 0 || $seek_end < ($file['size'] - 1)) && fseek($handle, $seek_start) !== -1) {
                        $length = ($seek_end - $seek_start + 1);

                        header('HTTP/1.1 206 Partial Content');
                        header('Accept-Ranges: bytes');
                        header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file['size']);
                        header('Content-Type: ' . $file['mimetype']);
                        header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
                        header('Content-Length: '.$length);

                        // start buffered download of 8 KB chunks while the connection is alive
                        $transfered = 0;
                        while (!feof($handle) && connection_status() == CONNECTION_NORMAL && $transfered < $length) {

                            // reset time limit for big files
                            set_time_limit(0);

                            // @codingStandardsIgnoreStart
                            echo fread($handle, 8192);
                            // @codingStandardsIgnoreEnd

                            $transfered += 8192;
                            flush();
                            ob_flush();
                        } // while

                        fclose($handle);
                        exit;
                    } // if fseek
                } // foreach ranges
            } // if unit bytes
        } // if http_range


        // prepare headers
        header('Last-Modified: '.$file['modify']);
        header('Accept-Ranges: bytes');
        header('Content-Type: '.$file['mimetype']);
        header('Content-Length: '.$file['size']);
        header('Content-Transfer-Encoding: binary');

        session_cache_limiter(false);

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= strtotime($file['modify'])
        ) {
            header('HTTP/1.0 304 Not Modified');
            exit;
        }

        if (is_resource($handle)) {

            // write to output buffer in small chunks to bypass memory limit of large files (which fpassthru would
            // exceed)
            while (!feof($handle) && connection_status() == CONNECTION_NORMAL) {

                // reset time limit for big files
                set_time_limit(0);

                // @codingStandardsIgnoreStart
                echo fread($handle, 8192);
                // @codingStandardsIgnoreEnd

                flush();
                ob_flush();
            } // while
            fclose($handle);
        } elseif (Config::get('use_sendfile') && in_array('mod_xsendfile', apache_get_modules())) {
            header('X-Sendfile: '.$this->path_sendfile);
        } else {
            readfile($this->path_sendfile);
            exit;
        }
    } // function
}// class
