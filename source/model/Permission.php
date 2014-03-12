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
 * Permission repository.
 *
 * @package FeM\sPof\model
 * @author pegro
 * @since 1.0
 */
abstract class Permission extends AbstractModelWithId
{
    /**
     * Referenced table.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_permission';


    /**
     * @internal
     *
     * @param array $input
     */
    public static function validate(array $input)
    {
        self::getValidator($input)
            ->isEmpty('name', 'Permission name war leer.')
            ->validate();
    } // function


    /**
     * Get all permissions.
     *
     * @api
     *
     * @return array
     */
    public static function getAll()
    {
        $stmt = self::createStatement(
            "
            SELECT
                id,
                name,
                description,
                default_privacy_group,
                (SELECT COUNT(r.user_id) FROM rel_group_user_permission r WHERE r.permission_id = id) AS user_count
            FROM tbl_permission
            ORDER BY name
            "
        );

        return $stmt->fetchAll();
    } // function


    /**
     * Get all directly assigned permissions of a user.
     *
     * @api
     *
     * @param int $user_id
     *
     * @return array
     */
    public static function getByUserId($user_id)
    {
        // order should be the same as in self::getAll(), edit script currently needs it that way
        $stmt = self::createStatement(
            "
            SELECT
                id,
                name,
                group_id,
                group_name,
                explicit
            FROM view_user_group_permission
            WHERE
                user_id = :user_id
                AND direct_permission IS TRUE
            "
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchAll();
    } // function


    /**
     * Get all users which are directly assigned to a permission.
     *
     * @api
     *
     * @param string $name permission name
     * @param int $group_id
     *
     * @return array
     */
    public static function getByName($name, $group_id = null)
    {
        if (empty($name)) {
            return [];
        }

        if ($group_id === null) {
            $stmt = self::createStatement(
                "
                SELECT
                    user_id,
                    group_id,
                    group_name,
                    explicit
                FROM view_user_group_permission
                WHERE
                    name = :name
                    AND direct_permission IS TRUE
                "
            );
            $stmt->assign('name', $name);
        } else {

            $stmt = self::createStatement(
                "
                SELECT
                    user_id,
                    group_id,
                    group_name,
                    explicit
                FROM view_user_group_permission
                WHERE
                    name = :name
                    AND group_id = :group_id
                    AND direct_permission IS TRUE
                "
            );
            $stmt->assign('name', $name);
            $stmt->assignId('group_id', $group_id);
        }

        return $stmt->fetchAll();
    } // function


    /**
     * Apply permission to user for group.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     * @param int $permission_id
     *
     * @return bool true on success
     */
    public static function applyToUser($user_id, $group_id, $permission_id)
    {
        $stmt = self::createStatement(
            "
            INSERT INTO rel_group_user_permission (group_id, user_id, permission_id)
            VALUES (
                :group_id,
                :user_id,
                :permission_id)
            "
        );
        $stmt->assignId('group_id', $group_id);
        $stmt->assignId('user_id', $user_id);
        $stmt->assignId('permission_id', $permission_id);

        return $stmt->affected();
    } // function


    /**
     * Revoke permission from a user for group.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     * @param int $permission_id
     *
     * @return bool true on success
     */
    public static function revoke($user_id, $group_id, $permission_id)
    {
        $stmt = self::createStatement(
            "
            DELETE
            FROM rel_group_user_permission
            WHERE
                group_id = :group_id
                AND user_id = :user_id
                AND permission_id = :permission_id
            "
        );
        $stmt->assignId('group_id', $group_id);
        $stmt->assignId('user_id', $user_id);
        $stmt->assignId('permission_id', $permission_id);

        return $stmt->affected();
    } // function


    /**
     * Get all users which have the permission applied.
     *
     * @api
     *
     * @param int $permission_id
     *
     * @return array
     */
    public static function getUsersById($permission_id)
    {
        // order should be the same as in self::getAll(), edit script currently needs it that way
        $stmt = self::createStatement(
            "
            SELECT
                u.name AS user_name,
                r.user_id,
                g.name AS group_name,
                r.group_id
            FROM rel_group_user_permission r
            JOIN tbl_group g ON r.group_id=g.id
            JOIN tbl_user u ON r.user_id=u.id
            WHERE r.permission_id=:permission_id
            ORDER BY
                r.group_id ASC,
                u.name ASC
            "
        );
        $stmt->assignId('permission_id', $permission_id);

        return $stmt->fetchAll();
    } // function


    /**
     * Check if a permission is applied to the user
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     * @param string $permission
     *
     * @return bool
     */
    public static function isApplied($user_id, $group_id, $permission)
    {
        if (empty($permission)) {
            return false;
        }

        $stmt = self::createStatement(
            "
            SELECT TRUE
            FROM view_user_group_permission
            WHERE
                user_id=:user_id
                AND group_id=:group_id
                AND name @> :permission
            LIMIT 1
            "
        );
        $stmt->assignId('user_id', $user_id);
        $stmt->assignId('group_id', $group_id);
        $stmt->assign('permission', $permission);

        return $stmt->fetchColumn() !== false;
    } // function


    /**
     * Get the default privacy group for a user (or the default one as fallback).
     *
     * @api
     *
     * @param string $permission
     * @param int $user_id
     *
     * @return string|false false if nothing was found
     */
    public static function getPrivacyGroup($permission, $user_id = 0)
    {
        if (empty($permission)) {
            return false;
        }

        $hashName = __METHOD__.'.'.md5(serialize(func_get_args()));

        $privacy = Cache::fetch($hashName);
        if ($privacy !== false) {
            return $privacy;
        }

        $stmt = self::createStatement(
            "
            SELECT COALESCE(pr.privacy_group, p.default_privacy_group) AS privacy_group
            FROM tbl_permission p
            LEFT JOIN tbl_privacy pr
                ON p.id = pr.permission_id
                AND pr.user_id = :user_id
            WHERE p.name=:permission
            "
        );
        $stmt->assignId('user_id', $user_id);
        $stmt->assign('permission', $permission);

        $privacy = $stmt->fetchColumn();
        if ($privacy !== false) {
            Cache::store($hashName, $privacy, 300);
        }
        return $privacy;
    } // function
}// class
