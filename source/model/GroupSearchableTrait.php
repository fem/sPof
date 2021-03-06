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
 * Implement a search via fti (full text index) of the table with restrictions to the groups. Only usable for models!
 *
 * @package FeM\sPof\model
 * @author thillux
 * @since 1.0
 */
trait GroupSearchableTrait
{
    /**
     * Standard interface for search via full text index (with group constraint).
     *
     * @api
     *
     * @param string $query
     * @param string $user_groups (optional) comma separated groups
     *
     * @return int
     */
    public static function getCountBySearch($query, $user_groups)
    {
        if (empty($query)) {
            return false;
        }

        $stmt = DBConnection::getInstance()->prepare(
            "
            SELECT COUNT(*)
            FROM ".self::$TABLE."
            WHERE
                fti @@ plainto_tsquery(:query)
                AND group_id IN (".$user_groups.")
            "
        );
        $stmt->assign('query', $query);

        return $stmt->fetchColumn();
    } // function


    /**
     * Standard interface for search via full text index (with group constraint).
     *
     * @api
     *
     * @param string $query tsearch query
     * @param int $limit (optional)
     * @param int $offset (optional)
     * @param string $user_groups (optional) comma separated groups
     *
     * @return array
     */
    public static function search($query, $limit = 30, $offset = 0, $user_groups = "")
    {
        if (empty($query)) {
            return [];
        }

        $stmt = self::createStatement(
            "
            SELECT *
            FROM ".self::$TABLE."
            WHERE
                fti @@ plainto_tsquery(:query)
                AND group_id IN (".$user_groups.")
            ORDER BY creation DESC
            LIMIT :limit
            OFFSET :offset
            "
        );
        $stmt->assign('query', $query);
        $stmt->assignInt0('limit', $limit);
        $stmt->assignInt0('offset', $offset);

        return $stmt->fetchAll();
    } // function
}// trait
