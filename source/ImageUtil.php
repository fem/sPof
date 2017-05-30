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
 * Collection of functions related to images.
 *
 * @package FeM\sPof
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
abstract class ImageUtil
{
    /**
     * Resize a Image file.
     *
     * @api
     *
     * @param string $in_filename
     * @param string $out_filename
     *
     * @return string new filename
     */
    public static function gif2png($in_filename, $out_filename)
    {
        if (!file_exists($in_filename) || filesize($in_filename) <= 0) {
            return false;
        }

        // try to load as jpeg first
        try {
            $image = imagecreatefromgif($in_filename);
        } catch (\ErrorException $e) {
            try {
                $image = imagecreatefromjpeg($in_filename);
            } catch (\ErrorException $e) {
                throw $e;
            }
        }

        // Ausgabe-Bild in Ausgabe-Datei schreiben
        imagepng($image, $out_filename);

        // Speicher wieder frei machen
        imagedestroy($image);
        return true;
    }


    /**
     * Resize a Image file.
     *
     * @api
     *
     * @param string $in_filename
     * @param string $out_filename
     * @param int $maxW (optional)
     * @param int $maxH (optional)
     * @param bool $cropimage (optional)
     *
     * @return bool
     */
    public static function resize($in_filename, $out_filename, $maxW = 100, $maxH = 100, $cropimage = false, $autocrop = false)
    {
        if (!file_exists($in_filename) || filesize($in_filename) <= 0 || $maxW <= 0 || $maxH <= 0) {
            return false;
        }

        // try to load as jpeg first
        try {
            $in_image = imagecreatefromjpeg($in_filename);
            $mimetype = 'image/jpeg';
        } catch (\ErrorException $e) {
            Logger::getInstance()->debug('Could not load image as JPEG: ' . $e->getMessage());

            // seems no jpeg, try png instead
            try {
                $in_image = imagecreatefrompng($in_filename);

                // preserve alpha channel
                imagealphablending($in_image, false);
                imagesavealpha($in_image, true);

                $mimetype = 'image/png';
            } catch (\ErrorException $e) {
                Logger::getInstance()->debug('Could not load image as PNG: '. $e->getMessage());
                return false;
            }
        }

        // Auto crop
        if($autocrop) {
            $cropped = imagecropauto($in_image, IMG_CROP_DEFAULT);
            if ($cropped !== false) {       // in case a new image resource was returned
                imagedestroy($in_image);    // we destroy the original image
                $in_image = $cropped;       // and assign the cropped image to $im
            }
        }

        // Geometrie des Eingabe-Bildes ermitteln
        $width = imagesx($in_image);
        $height = imagesy($in_image);

        // obere Ecke bestimmen
        $left = 0;
        $top = 0;

        // Ratios bestimmen
        $ratio  = $width / $height;       // Quelle
        $ratio2 = $maxW / $maxH; // Ziel

        // Bild nur verkleinern und nicht vergroessern
        if (($width <= $maxW) && ($height <= $maxH) && !$cropimage) {

            // Ausgabe-Bild in Ausgabe-Datei schreiben
            switch ($mimetype) {
                case 'image/jpeg':
                    imagejpeg($in_image, $out_filename, 100);
                    break;
                case 'image/png':
                    imagepng($in_image, $out_filename);
                    break;
            } // switch

            // Speicher wieder frei machen
            imagedestroy($in_image);
            return true;
        }

        // Geometrie des Ausgabe-Bildes berechnen
        if (!$cropimage) {
            if ($maxW / $width < $maxH / $height) {
                $width2 = $maxW;
                $height2 = round($height * $width2 / $width);
            } else {
                $height2 = $maxH;
                $width2 = round($width * $height2 / $height);
            }
        } else {
            $width2 = $maxW;
            $height2 = $maxH;
            $left = 0;
            $top = 0;
            if ($ratio2 < $ratio) {
                $width3 = $height * $ratio2;
                $left = ($width - $width3) / 2;
                $width = $width3;
            } else {
                $height = $width / $ratio2;
            }
        }

        // Ausgabe-Bild erstellen
        $out_image = imagecreatetruecolor($width2, $height2);

        // allocate transparent color (becausec)
        imagecolorallocatealpha($out_image, 0, 0, 0, 127);

        // preserve alpha channel
        imagealphablending($out_image, false);
        imagesavealpha($out_image, true);

        // Ausgabe-Bild berechnen
        imagecopyresampled($out_image, $in_image, 0, 0, $left, $top, $width2, $height2, $width, $height);

        // Ausgabe-Bild in Ausgabe-Datei schreiben
        switch ($mimetype) {
            case 'image/jpeg':
                imagejpeg($out_image, $out_filename, 100);
                break;
            case 'image/png':
                imagepng($out_image, $out_filename);
                break;
        } // switch

        // Speicher wieder frei machen
        imagedestroy($in_image);
        imagedestroy($out_image);
        return true;
    } // function
}// class
