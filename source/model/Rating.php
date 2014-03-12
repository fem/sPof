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

namespace FeM\sPof\model;

/**
 * Rating interface.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @since 1.0
 */
interface Rating
{
    /**
     * A positive rating value.
     *
     * @api
     *
     * @var int
     */
    const LIKE    = 1;

    /**
     * A neutral rating value
     *
     * @api
     *
     * @var int
     */
    const WAYNE   = 0;


    /**
     * Add a new rating for an item.
     *
     * @api
     *
     * @param int $item_id
     * @param int $user_id
     * @param int $rate see class constants
     */
    public static function addRating($item_id, $user_id, $rate);


    /**
     * Get the number of how often a rating was applied to a item.
     *
     * @api
     *
     * @param int $item_id
     * @param int $rating see class constants
     *
     * @return int
     */
    public static function getRating($item_id, $rating);
}// class
