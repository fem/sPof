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
 * Role repository.
 *
 * @package FeM\sPof\model
 * @author pegro
 * @since 1.0
 */
class Role extends AbstractModelWithId
{
    /**
     * Referenced table.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_role';


    /**
     * @internal
     *
     * @param array $input
     */
    public static function validate(array $input)
    {
        self::getValidator($input)
            ->isEmpty('name', _('name must not be empty'))
            ->validate();
    } // function


    /**
     * Return all Roles.
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
                (SELECT COUNT(r.user_id) FROM rel_group_user_role r WHERE r.role_id = id) AS user_count
            FROM tbl_role
            ORDER BY name
            "
        );

        return $stmt->fetchAll();
    } // function


    /**
     * Remove all permissions from role.
     *
     * @api
     *
     * @param int $role_id
     *
     * @return bool returns true of rows are deleted, false if nothing happend.
     */
    public static function resetById($role_id)
    {
        $stmt = self::createStatement(
            "
            DELETE
            FROM rel_role_permission
            WHERE role_id=:role_id
            "
        );
        $stmt->assignId('role_id', $role_id);

        return $stmt->affected();
    } // function


    /**
     * Get all roles which are assignable in a specific subtree of a group-tree.
     *
     * @api
     *
     * @param int $group_id
     *
     * @return array
     */
    public static function getAssignableForGroup($group_id)
    {
        // order should be the same as in self::getAll(), edit script currently needs it that way
        $stmt = self::createStatement(
            "
            SELECT
                r.id,
                r.name
            FROM tbl_role r
            JOIN tbl_group g ON g.id=r.assignable_in_subtree
            JOIN tbl_group g2 ON g2.n_root=g.n_root
            WHERE
                r.assignable_in_subtree IS NOT NULL
                AND g2.id=:group_id
                AND g2.n_left <= g.n_left
                AND g2.n_right >= g.n_right
            ORDER BY r.name ASC
            "
        );
        $stmt->assignId('group_id', $group_id);

        return $stmt->fetchAll();
    } // function getByUserAndGroupId


    /**
     * Get all roles of user in a specific group.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     *
     * @return array
     */
    public static function getByUserAndGroupId($user_id, $group_id)
    {
        // order should be the same as in self::getAll(), edit script currently needs it that way
        $stmt = self::createStatement(
            "
            SELECT
                r.id,
                r.name,
                r.description
            FROM tbl_role r
            JOIN rel_group_user_role rel ON rel.role_id=r.id
            WHERE
                rel.user_id=:user_id
                AND rel.group_id=:group_id
            ORDER BY r.name ASC
            "
        );
        $stmt->assignId('user_id', $user_id);
        $stmt->assignId('group_id', $group_id);

        return $stmt->fetchAll();
    } // function getByUserAndGroupId


    /**
     * Get all roles of user.
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
                r.id,
                r.name,
                rel.group_id,
                g.name AS group_name
            FROM tbl_role r
            JOIN rel_group_user_role rel ON rel.role_id=r.id
            JOIN tbl_group g ON rel.group_id = g.id
            WHERE rel.user_id=:user_id
            ORDER BY
                r.name ASC,
                rel.group_id ASC
            "
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchAll();
    } // function


    /**
     * Returns the ids of all roles containing the permission with the given id.
     *
     * @api
     *
     * @param int $permission_id The id of the permission that must be contained in the returned roles.
     *
     * @return array
     */
    public static function getByPermissionId($permission_id)
    {
        // order should be the same as in self::getAll(), edit script currently needs it that way
        $stmt = self::createStatement(
            "
            SELECT role_id
            FROM rel_role_permission
            WHERE permission_id = :permission_id
            "
        );
        $stmt->assignId('permission_id', $permission_id);

        return $stmt->fetchAll();
    } // function


    /**
     * Add a new permission to a role
     *
     * @api
     *
     * @param int $role_id
     * @param int $permission_id
     *
     * @return bool true on success
     */
    public static function addPermission($role_id, $permission_id)
    {
        $stmt = self::createStatement(
            "
            INSERT INTO rel_role_permission (role_id, permission_id)
            VALUES (
                :role_id,
                :permission_id)
            "
        );
        $stmt->assignId('role_id', $role_id);
        $stmt->assignId('permission_id', $permission_id);

        return $stmt->affected();
    } // function


    /**
     * Get all permissions of a role.
     *
     * @api
     *
     * @param int $role_id
     *
     * @return array
     */
    public static function getPermissionsById($role_id)
    {
        $stmt = self::createStatement(
            "
            SELECT
                p.id,
                p.name,
                p.description
            FROM tbl_permission p
            JOIN rel_role_permission r ON r.permission_id=p.id
            WHERE r.role_id=:role_id
            ORDER BY name ASC
            "
        );
        $stmt->assignId('role_id', $role_id);

        return $stmt->fetchAll();
    } // function


    /**
     * Apply role to a user for group. This function also assures that an entry is not inserted twice.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     * @param int $role_id (optional)
     *
     * @return bool true on success
     */
    public static function applyToUser($role_id, $user_id, $group_id = null)
    {
        $stmt = self::createStatement(
            "
            INSERT INTO rel_group_user_role ( group_id, user_id, role_id, disabled )
            VALUES (
                :group_id,
                :user_id,
                :role_id,
                FALSE)
            "
        );
        $stmt->assignId('role_id', $role_id);
        $stmt->assignId('user_id', $user_id);
        $stmt->assign('group_id', $group_id);

        return $stmt->affected();
    } // function


    /**
     * Revoke role from a user for group.
     *
     * @api
     *
     * @param int $role_id
     * @param int $user_id
     * @param int $group_id (optional)
     *
     * @return bool true on success
      */
    public static function revoke($role_id, $user_id, $group_id = null)
    {
        $stmt = self::createStatement(
            "
            DELETE
            FROM rel_group_user_role
            WHERE
                group_id = :group_id
                AND user_id = :user_id
                AND role_id = :role_id
            "
        );
        $stmt->assignId('user_id', $user_id);
        $stmt->assignId('role_id', $role_id);
        $stmt->assign('group_id', $group_id);

        return $stmt->affected();
    } // function


    /**
     * Get all users which are members of the role.
     *
     * @api
     *
     * @param int $role_id
     *
     * @return array
     */
    public static function getUsersById($role_id)
    {
        // order should be the same as in self::getAll(), edit script currently needs it that way
        $stmt = self::createStatement(
            "
            SELECT
                u.name AS user_name,
                u.firstname,
                u.lastname,
                r.user_id,
                g.name AS group_name,
                r.group_id
            FROM rel_group_user_role r
            JOIN tbl_group g ON r.group_id = g.id
            JOIN tbl_user u ON r.user_id = u.id
            WHERE r.role_id = :role_id
            ORDER BY r.group_id, u.name ASC
            "
        );
        $stmt->assignId('role_id', $role_id);

        return $stmt->fetchAll();
    } // function
}// class
