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
use FeM\sPof\FileUtil;
use FeM\sPof\Request;
use FeM\sPof\ImageUtil;

/**
 * Show resized thumb image.
 *
 * @package FeM\sPi\view
 * @author pegro
 * @since 1.0
 */
class RpcImageView extends RpcFileView
{
    /**
     * Width of the target image.
     *
     * @internal
     *
     * @var int
     */
    protected $width = 0;

    /**
     * Height of the target image.
     *
     * @internal
     *
     * @var int
     */
    protected $height = 0;

    /**
     * Crop image or scale to boundaries.
     *
     * @internal
     *
     * @var bool
     */
    protected $cropimage = false;

    /**
     * Make a thumb image.
     *
     * @internal
     */
    public function thumb()
    {
        $this->width = Request::getIntParam('width', 100);
        $this->height = Request::getIntParam('height', 100);
        $this->cropimage = Request::getBoolParam('cropimage', false);

        $this->processing = true;
        $this->stats_disable = true;
        $this->path_sendfile = sprintf(
            Application::$FILE_ROOT.'tmp/thumb/%s/%s_%sx%s.thumb',
            $this->sid[0],
            $this->sid,
            $this->width,
            $this->height
        );
        $this->download();
    } // function


    /**
     * Generating thumbnail from cached file of matching sid
     *
     * If global variable $path_sendfile is set and variable $processing is true
     * download.php will call this function with a file info array with metadata.
     *
     * Afterwards download.php checks existance and updates metadata of file at $path_sendfile.
     *
     * @api
     *
     * @param array   $file database entry and cachefile path of file matching the sid
     */
    protected function processCachefile($file)
    {
        if (isset($file['mimetype']) && ($file['mimetype'] !== 'image/jpeg' && $file['mimetype'] !== 'image/png')) {
            self::sendInternalError();
        }

        if (!FileUtil::makedir(dirname($this->path_sendfile))) {
            self::sendInternalError();
        }

        if (!ImageUtil::resize(
            $file['cachefile'],
            $this->path_sendfile,
            $this->width,
            $this->height,
            $this->cropimage
        )) {
            self::sendInternalError();
        }
    } // function
}// class
