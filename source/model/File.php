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

use FeM\sPof\Cache;

/**
 * File repository.
 *
 * @package FeM\sPof\model
 * @author deka
 * @author pegro
 * @since 1.0
 */
abstract class File extends AbstractModelWithId
{
    /**
     * Referenced table.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_file';


    /**
     * Return total number of file requests.
     *
     * @api
     *
     * @param $file_id
     * @return int|false
     */
    public static function getAccessCount($file_id)
    {
        $count = Cache::fetch('file_access_count.'.$file_id);
        if ($count !== false) {
            return $count;
        }

        $stmt = self::createStatement(
            "
            SELECT SUM(accesscount)
            FROM tbl_statistic_file
            WHERE file_id = :file_id
            "
        );
        $stmt->assignId('file_id', $file_id);

        $count = $stmt->fetchInt();
        Cache::store('file_access_count.'.$file_id, $count, 300);

        return $count;
    } // function


    /**
     * Return the number of distinct users which accessed the file
     *
     * @api
     *
     * @param int $file_id
     * @return int|false
     */
    public static function getUserCount($file_id)
    {
        $count = Cache::fetch('file_user_count.'.$file_id);
        if ($count !== false) {
            return $count;
        }

        $stmt = self::createStatement(
            "
            SELECT count(DISTINCT user_id)
            FROM tbl_statistic_file
            WHERE file_id = :file_id
            "
        );
        $stmt->assignId('file_id', $file_id);

        $count = $stmt->fetchInt();
        Cache::store('file_user_count.'.$file_id, $count, 300);

        return $count;
    } // function


    /**
     * Get number of files a user has stored.
     *
     * @api
     *
     * @param int $user_id
     * @param array $exclude_tables (optional)
     *
     * @return array
     */
    public static function getOverallCountByUserId($user_id, $exclude_tables = [])
    {
        if (empty($exclude_tables)) {
            $stmt = self::createStatement(
                "
                SELECT count(*)
                FROM tbl_file
                WHERE user_id = :user_id
                "
            );
        } else {
            $stmt = self::createStatement(
                "
                SELECT count(*)
                FROM tbl_file f
                JOIN pg_class p ON f.tableoid = p.oid
                WHERE
                    user_id = :user_id
                    AND p.relname <> ALL('{" . implode(',', $exclude_tables) . "}')
                "
            );
        }

        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchColumn();
    } // function


    /**
     * Replace the owner of files by another.
     *
     * @api
     *
     * @param int $user_id
     * @param int $replacing_user_id
     * @param array $exclude_tables (optional)
     *
     * @return array
     */
    public static function replaceUserId($user_id, $replacing_user_id, $exclude_tables = [])
    {
        if (empty($exclude_tables)) {
            $stmt = self::createStatement(
                "
                UPDATE tbl_file
                SET user_id = :replacing_user_id
                WHERE user_id = :user_id
                "
            );
        } else {
            $stmt = self::createStatement(
                "
                UPDATE tbl_file f
                SET user_id = :replacing_user_id
                FROM pg_class p
                WHERE
                    f.tableoid = p.oid
                    AND f.user_id = :user_id
                    AND p.relname <> ALL('{" . implode(',', $exclude_tables) . "}')
                "
            );
        }
        $stmt->assignId('user_id', $user_id);
        $stmt->assignId('replacing_user_id', $replacing_user_id);

        return $stmt->fetch();
    } // function
}// class
