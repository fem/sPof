<?php
/**
 * This file is part of sPof.
 *
 * FIXME license
 *
 * @copyright 2003-2014 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
 * @link      http://spof.fem-net.de
 */

namespace FeM\sPof\model;

use FeM\sPof\model\SearchableTrait;
use FeM\sPof\Session;

/**
 * Group repository.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
abstract class Group extends AbstractModelWithId
{
    use SearchableTrait;

    /**
     * Referenced table name.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_group';


    /**
     * @internal
     *
     * @param array $input
     */
    protected static function validate(array $input)
    {
        self::getValidator($input)
            ->isEmpty('name', 'Es wurde kein Name angegeben.')
            ->isEmpty('shortname', 'Es wurde kein Kurzname angegeben.')
            ->isLt('parent', 0, 'Es wurde eine ungültige Obergruppe angegeben.')
            ->validate();
    } // function


    /**
     * Get All Groups.
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
                shortname
            FROM tbl_group
            ORDER BY n_left ASC
            "
        );

        return $stmt->fetchAll();
    } // function


    /**
     * Get all Groups where the given user is member.
     *
     * @api
     *
     * @param int $user_id
     * @param bool $hideprimegroup (optional)
     * @param bool $direct_member_only (optional)
     *
     * @return array
     */
    public static function getByUserId($user_id, $hideprimegroup = true, $direct_member_only = true)
    {
        if ($direct_member_only) {
            $stmt = self::createStatement(
                "
                SELECT
                    g.*,
                    p.name AS parent_name,
                    (SELECT COUNT(*) FROM rel_user_group WHERE group_id=g.id AND requested IS FALSE) AS membercount
                FROM view_user_group_membership g
                LEFT JOIN tbl_group p ON p.id=g.parent
                WHERE
                    g.member_user_id=:user_id
                    AND g.direct_membership IS TRUE
                ORDER BY
                    g.n_root ASC,
                    g.n_left ASC
                OFFSET :offset
                "
            );
        } else {
            $stmt = self::createStatement(
                "
                SELECT
                    g.*,
                    p.name AS parent_name,
                    (SELECT COUNT(*) FROM rel_user_group WHERE group_id=g.id AND requested IS FALSE) AS membercount
                FROM view_user_group_membership g
                LEFT JOIN tbl_group p ON p.id=g.parent
                WHERE g.member_user_id=:user_id
                ORDER BY
                    g.n_root ASC,
                    g.n_left ASC
                OFFSET :offset
                "
            );
        }
        $stmt->assignInt0('offset', ($hideprimegroup == true));
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchAll();
    } // function


    /**
     * Return amount of groups where the user is direct member.
     *
     * @api
     *
     * @param int $user_id
     *
     * @return int|false
     */
    public static function getCountByUserId($user_id)
    {
        $stmt = self::createStatement(
            "
            SELECT COUNT(*)
            FROM view_user_group_membership g
            WHERE
                g.member_user_id=:user_id
                AND g.direct_membership IS TRUE
            "
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchInt();
    } // function


    /**
     * Return groups where user is direct member, suitable for pagination.
     *
     * @api
     *
     * @param int $user_id
     * @param int $limit (optional)
     * @param int $offset (optional)
     *
     * @return array
     */
    public static function getByUserIdWithLimit($user_id, $limit = 20, $offset = 0)
    {
        $stmt = self::createStatement(
            "
            SELECT
                g.*,
                g2.shortname AS parentname,
                (SELECT COUNT(*) FROM rel_user_group WHERE group_id=g.id AND requested IS FALSE) AS membercount
            FROM view_user_group_membership g
            LEFT JOIN tbl_group g2 ON g2.id=g.parent
            WHERE
                g.member_user_id=:user_id
                AND g.direct_membership IS TRUE
            ORDER BY g.n_root ASC, g.n_left ASC
            LIMIT :limit
            OFFSET :offset
            "
        );
        $stmt->assignInt0('limit', $limit);
        $stmt->assignInt0('offset', $offset);
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchAll();
    } // function


    /**
     * Return a random group where the user is member.
     *
     * @api
     *
     * @param int $user_id
     * @param int $limit (optional)
     *
     * @return array
     */
    public static function getRandomByUserId($user_id, $limit = 1)
    {
        $stmt = self::createStatement(
            "
            SELECT
                g.*,
                g2.shortname AS parentname,
                (SELECT COUNT(*) FROM rel_user_group WHERE group_id=g.id AND requested IS FALSE) AS membercount
            FROM view_user_group_membership g
            LEFT JOIN tbl_group g2 ON g2.id=g.parent
            WHERE
                g.member_user_id=:user_id
                AND g.direct_membership IS TRUE
            ORDER BY RANDOM()
            LIMIT :limit
            "
        );
        $stmt->assignId('user_id', $user_id);
        $stmt->assignInt0('limit', $limit);

        return $stmt->fetchAll();
    } // function


    /**
     * Return the subgroups of a group.
     *
     * @api
     *
     * @param int $group_id
     * @param int $level (optional) limit the level of subgroups
     *
     * @return array
     */
    public static function getChildTreeById($group_id, $level = 999)
    {
        $stmt = self::createStatement(
            "
            SELECT
                g.id,
                g.name,
                g.shortname,
                g.description,
                g.n_level,
                g.joinable,
                count_group_user(g.id) AS count_thisgroup,
                count_group_user_recursive(g.id) AS membercount
            FROM tbl_group g
            JOIN tbl_group g2 ON g2.n_root = g.n_root
            WHERE
                g2.id=:group_id
                AND g.n_left > g2.n_left
                AND g.n_right < g2.n_right
                AND g.n_level <= g2.n_level + :level
            ORDER BY g.n_left ASC
            "
        );
        $stmt->assignId('group_id', $group_id);
        $stmt->assignInt0('level', $level);

        return $stmt->fetchAll();
    } // function


    /**
     * Get membership requests for a user.
     *
     * @api
     *
     * @param int $user_id
     *
     * @return array
     */
    public static function getRequestsByUserId($user_id)
    {
        $stmt = self::createStatement(
            "
            SELECT
                u.name AS user_name,
                u.firstname AS user_firstname,
                u.lastname AS user_lastname,
                r.modify,
                r.group_id,
                r.user_id,
                r.comment,
                g.name AS group_name
            FROM rel_user_group r
            JOIN tbl_user u ON u.id=r.user_id
            JOIN tbl_group g ON g.id=r.group_id
            WHERE
                r.requested IS TRUE
                AND r.user_id=:user_id
            ORDER BY r.modify ASC
            "
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchAll();
    } // getRequestsByDserId


    /**
     * Get a group by name.
     *
     * @api
     *
     * @param string $name
     *
     * @return array|false
     */
    public static function getByName($name)
    {
        if (empty($name)) {
            return false;
        }

        $stmt = self::createStatement(
            "
            SELECT
                id,
                name
            FROM tbl_group
            WHERE name=:name
            "
        );
        $stmt->assign('name', $name);

        return $stmt->fetch();
    } // function


    /**
     * Check if a user is member of a group.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     * @param bool $direct_membership (optional)
     *
     * @return bool
     */
    public static function isUserMember($user_id, $group_id, $direct_membership = true)
    {
        if ($direct_membership) {
            $stmt = self::createStatement(
                "
                SELECT sort
                FROM view_user_group_membership
                WHERE
                    id=:group_id
                    AND member_user_id=:user_id
                    AND direct_membership IS TRUE
                "
            );
        } else {
            $stmt = self::createStatement(
                "
                SELECT sort
                FROM view_user_group_membership
                WHERE
                    id=:group_id
                    AND member_user_id=:user_id
                "
            );
        }
        $stmt->assignId('group_id', $group_id);
        $stmt->assignId('user_id', $user_id);

        return $stmt->affected();
    } // function


    /**
     * Check if a user as requested of membership for a group.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     * @param bool $direct_only (optional)
     *
     * @return bool
     */
    public static function hasUserRequestedMembership($user_id, $group_id, $direct_only = true)
    {
        if ($direct_only) {
            $stmt = self::createStatement(
                "
                SELECT 1
                FROM rel_user_group
                WHERE
                    requested IS TRUE
                    AND group_id=:group_id
                    AND user_id=:user_id
                    AND visible IS TRUE
                    AND disabled IS FALSE
                "
            );
        } else {
            $stmt = self::createStatement(
                "
                SELECT 1
                FROM tbl_group g
                JOIN tbl_group g2 ON g2.n_root=g.n_root
                JOIN rel_user_group r ON r.group_id = g2.id
                WHERE
                    g.id = :group_id
                    AND r.user_id = :user_id
                    AND r.requested IS TRUE
                    AND g2.n_left > g.n_left
                    AND g2.n_right < g.n_right
                    AND r.visible IS TRUE
                    AND r.disabled IS FALSE
                    AND g2.visible IS TRUE
                    AND g.disabled IS FALSE
                LIMIT 1
                "
            );
        }
        $stmt->assignId('group_id', $group_id);
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchColumn();
    } // function


    /**
     * Get all members of a group.
     *
     * @api
     *
     * @param int $group_id
     * @return array
     * @todo get rid of Session here
     */
    public static function getMembersById($group_id)
    {
        $stmt = self::createStatement(
            "
            SELECT
                u.id AS user_id,
                u.name AS user_name,
                u.firstname,
                u.lastname,
                u.lastupdate,
                u.email,
                u.email AS email_uni,
                b.target_user_id AS buddy,
                (
                    SELECT d.title
                    FROM tbl_diary d
                    WHERE d.user_id=u.id
                        AND d.disabled IS FALSE
                    ORDER BY d.creation DESC
                    LIMIT 1
                ) AS diary_title,
                (
                    SELECT string_agg(class_names, ' ')
                    FROM tbl_role ro
                    JOIN rel_group_user_role rr ON (rr.role_id=ro.id AND rr.group_id=r.group_id)
                    WHERE rr.user_id=u.id AND class_names IS NOT NULL
                ) AS class_names
            FROM tbl_user u
            JOIN rel_user_group r ON r.user_id=u.id
            LEFT JOIN tbl_buddy b ON b.target_user_id=u.id AND b.owner_user_id=:user_id
            WHERE
                r.group_id=:group_id
                AND r.requested IS FALSE
                AND r.visible IS TRUE
                AND r.disabled IS FALSE
            ORDER BY u.name ASC
            "
        );
        $stmt->assignId('group_id', $group_id);
        $stmt->assignId('user_id', Session::getUserId());

        return $stmt->fetchAll();
    } // function


    /**
     * Returns the number of requests for the given group.
     *
     * @api
     *
     * @param int $group_id
     * @param bool $subgroups (optional)
     *
     * @return int
     */
    public static function countRequests($group_id, $subgroups = false)
    {
        if ($subgroups === false) {
            $stmt = self::createStatement(
                "
                SELECT COUNT(*)
                FROM rel_user_group
                WHERE
                    requested IS TRUE
                    AND group_id=:group_id
                "
            );
        } else {
            $stmt = self::createStatement(
                "
                SELECT COUNT(*)
                FROM tbl_user u
                JOIN rel_user_group r ON r.user_id=u.id
                JOIN tbl_group g ON g.id=r.group_id
                JOIN tbl_group g2 ON g2.n_root = g.n_root
                WHERE
                    r.requested IS TRUE
                    AND g2.id=:group_id
                    AND g2.n_left <= g.n_left
                    AND g2.n_right >= g.n_right
                "
            );
        }
        $stmt->assignId('group_id', $group_id);

        return $stmt->fetchColumn();
    } // function


    /**
     * This Function returns a set of users which requested membership for a specific group.
     *
     * @api
     *
     * @param int $group_id
     * @param bool $subgroups (optional) if true, subgroups will be returned too
     *
     * @return array
     */
    public static function getRequests($group_id, $subgroups = false)
    {
        if ($subgroups === false) {
            $stmt = self::createStatement(
                "
                SELECT
                    u.name,
                    u.firstname,
                    u.lastname,
                    r.user_id,
                      r.modify,
                    r.group_id,
                    g.name AS group_name,
                    r.comment,
                    1 AS admins
                FROM tbl_user u
                JOIN rel_user_group r ON r.user_id=u.id
                JOIN tbl_group g ON g.id=r.group_id
                WHERE
                    r.requested IS TRUE
                    AND r.group_id=:group_id
                "
            );
        } else {

            $stmt = self::createStatement(
                "
                SELECT
                    u.name,
                    u.firstname,
                    u.lastname,
                    r.user_id,
                    r.modify,
                    r.group_id,
                    CONCAT(
                        (
                            SELECT shortname
                            FROM tbl_group
                            WHERE id=g.parent
                            LIMIT 1
                        ),
                        '>',
                        COALESCE(g.shortname, g.name)
                    ) AS group_name,
                    r.comment
                FROM tbl_user u
                JOIN rel_user_group r ON r.user_id=u.id
                JOIN tbl_group g ON g.id=r.group_id
                JOIN tbl_group g2 ON g2.n_root = g.n_root
                WHERE
                    r.requested IS TRUE
                    AND g2.id=:group_id
                    AND g2.n_left <= g.n_left
                    AND g2.n_right >= g.n_right
                "
            );
        }
        $stmt->assignId('group_id', $group_id);

        return $stmt->fetchAll();
    } // function


    /**
     * Add a new membership request.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     * @param string $comment (optional)
     *
     * @return bool
     */
    public static function requestMembership($user_id, $group_id, $comment = '')
    {
        $input = [
            'user_id' => $user_id,
            'group_id' => $group_id,
            'comment' => $comment,
            'requested' => self::hasUserRequestedMembership($user_id, $group_id)
        ];
        self::getValidator($input)
            ->isNoId('user_id', 'Benutzer Id ist ungültig.')
            ->isNoId('group_id', 'Gruppen Id ist ungültig.')
            ->isTrue('requested', 'Mitgliedschaft wurde bereits beantragt.')
            ->validate();

        $stmt = self::createStatement(
            "
            INSERT INTO rel_user_group (user_id, group_id, comment, requested, visible, disabled)
            VALUES (
                :user_id,
                :group_id,
                :comment,
                TRUE,
                TRUE,
                FALSE)
            "
        );
        $stmt->assignId('group_id', $group_id);
        $stmt->assignId('user_id', $user_id);
        $stmt->assign('comment', $comment);

        return $stmt->affected();
    } // function


    /**
     * Cancel a membership request.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     *
     * @return bool
     */
    public static function cancelRequest($user_id, $group_id)
    {
        $stmt = self::createStatement(
            "
            DELETE
            FROM rel_user_group
            WHERE
                requested IS TRUE
                AND user_id=:user_id
                AND group_id=:group_id
            "
        );
        $stmt->assignId('group_id', $group_id);
        $stmt->assignId('user_id', $user_id);

        return $stmt->affected();
    } // function


    /**
     * Delete a membership or cancel a request.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     *
     * @return bool
     */
    public static function deleteMembership($user_id, $group_id)
    {
        $stmt = self::createStatement(
            "
            DELETE
            FROM rel_user_group
            WHERE
                user_id=:user_id
                AND group_id=:group_id
            "
        );
        $stmt->assignId('group_id', $group_id);
        $stmt->assignId('user_id', $user_id);

        return $stmt->affected();
    } // function


    /**
     * Accept a requested membership request.
     *
     * @api
     *
     * @param int $user_id
     * @param int $group_id
     *
     * @return bool
     */
    public static function acceptRequest($user_id, $group_id)
    {
        $stmt = self::createStatement(
            "
            UPDATE rel_user_group
            SET
                requested=FALSE,
                modify=now(),
                disabled=FALSE,
                visible=TRUE,
                comment=''
            WHERE
                requested IS TRUE
                AND user_id=:user_id
                AND group_id=:group_id
            "
        );
        $stmt->assignId('group_id', $group_id);
        $stmt->assignId('user_id', $user_id);

        return $stmt->affected();
    } // function


    /**
     * Get a list of all ancestor groups.
     *
     * @api
     *
     * @param int $group_id
     *
     * @return array|false
     */
    public static function getBreadcrumbById($group_id)
    {
        $stmt = self::createStatement(
            "
            SELECT
                id,
                name,
                shortname
            FROM tbl_group
            WHERE
                n_left <= (SELECT n_left FROM tbl_group WHERE id=:group_id)
                AND n_right >= (SELECT n_right FROM tbl_group WHERE id=:group_id)
            ORDER BY n_level ASC
            "
        );
        $stmt->assignId('group_id', $group_id);

        return $stmt->fetchAll();
    } // function


    /**
     * Get a list of preceding groups which have features enabled.
     *
     * @api
     *
     * @param int $group_id
     *
     * @return int
     */
    public static function getByFeatureInTree($group_id)
    {
        $stmt = self::createStatement(
            "
            SELECT g.id
            FROM tbl_group g
            JOIN tbl_group g2 ON g2.n_root = g.n_root
            WHERE
                g.show_features IS TRUE
                AND g2.id = :group_id
                AND g.n_left <= g2.n_left
                AND g.n_right >= g2.n_right
            ORDER BY g.n_level DESC
            LIMIT 1
            "
        );
        $stmt->assignId('group_id', $group_id);

        return $stmt->fetchColumn();
    } // function


    /**
     * Search for similiar names.
     *
     * @api
     *
     * @param string  $exact
     * @param string  $like
     *
     * @return array
     */
    public static function searchNameBySimilarity($exact, $like)
    {
        if (empty($exact) && empty($like)) {
            return [];
        }

        $stmt = self::createStatement(
            "
            SELECT
                name,
                :like::VARCHAR,
                CASE WHEN similarity(name,:exact) IS NULL THEN 0 ELSE similarity(name,:exact) END AS similarity
            FROM tbl_group
            WHERE
                name ILIKE :exact
                OR name ILIKE :like
            ORDER BY
                similarity DESC,
                name ASC
            LIMIT 10
            "
        );
        $stmt->assign('exact', $exact);
        $stmt->assign('like', $like);

        return $stmt->fetchAll();
    } // function


    /**
     * Count how often a specific permission is assigned to a group.
     *
     * @api
     *
     * @param int $group_id
     * @param string $permission
     *
     * @return array
     */
    public static function countByPermission($group_id, $permission)
    {
        $stmt = self::createStatement(
            "
            SELECT COUNT(*)
            FROM view_user_group_permission
            WHERE
                group_id=:group_id
                AND direct_permission IS TRUE
                AND name <@ :permission
            "
        );
        $stmt->assignId('group_id', $group_id);
        $stmt->assign('permission', $permission);

        return $stmt->fetchColumn();
    } // function
}// class
