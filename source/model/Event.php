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
 * Event repository.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @author mbyr
 * @author pegro
 * @since 1.0
 */
abstract class Event extends AbstractModelWithId implements Rating
{
    use SearchableTrait;

    /**
     * Referenced table.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_event';


    /**
     * @internal
     *
     * @param array $input
     */
    protected static function validate(array $input)
    {
        self::getValidator($input)
            ->isLengthGt('locality', 64, 'Der Ort ist zu lang.')
            ->isLengthGt('title', 8192, 'Der Beschreibungstext ist zu lang.')
            ->isLengthGt('description', 8192, 'Der Titel ist zu lang.')
            ->isEmpty('title', 'Es wurde kein Titel angegeben.')
            ->isEmpty('description', 'Es wurde keine Beschreibung angegeben.')
            ->isLt('beginning', 0, 'Der Startzeitpunkt ist ungültig.')
            ->isLt('ending', 0, 'Endzeitpunkt ist ungültig.')
            ->isLt('ending', $input['beginning'], 'Ende darf nicht vor dem Beginn sein.')
            ->isNoId('user_id', 'Benutzer id ist ungültig.')
            ->isNoId('group_id', 'Gruppen ID ist ungültig.')
            ->validate();
    } // function


    /**
     * Get all eventy by owner user
     *
     * @api
     *
     * @param int $user_id
     * @param string $tag (optional)
     *
     * @return array
     */
    public static function getByUserId($user_id, $tag = null)
    {
        $stmt = self::createStatement(
            "
            SELECT
                e.id,
                e.description,
                e.title,
                e.beginning,
                (e.beginning + e.length) AS ending,
                e.locality,
                e.user_id,
                e.group_id,
                e.creation,
                e.public,
                e.user_id,
                e.tags,
                e.repeat,
                e.modify,
                g.shortname AS group_shortname
            FROM ".self::$TABLE." e
            JOIN tbl_group g ON g.id=e.group_id
            WHERE
                e.user_id=:user_id
                ".($tag === null ? '' : "AND '{:tag}' <@ array_from_json(tags)")."
                AND e.visible IS TRUE
            "
        );
        $stmt->assignId('user_id', $user_id);
        if ($tag !== null) {
            $stmt->assign('tag', $tag);
        }

        return $stmt->fetchInt();
    } // function


    /**
     * Get all events by owner group.
     *
     * @api
     *
     * @param int $group_id
     * @param string $tag (optional)
     *
     * @return array
     */
    public static function getByGroupId($group_id, $tag = null)
    {
        $stmt = self::createStatement(
            "
            SELECT
                e.id,
                e.description,
                e.title,
                e.beginning,
                (e.beginning + e.length) AS ending,
                e.locality,
                e.user_id,
                e.group_id,
                e.creation,
                e.public,
                e.user_id,
                e.tags,
                e.repeat,
                e.modify,
                g.shortname AS group_shortname
            FROM ".self::$TABLE." e
            JOIN tbl_group g ON g.id=e.group_id
            WHERE
                e.group_id=:group_id
                ".($tag === null ? '' : "AND '{:tag}' <@ array_from_json(tags)")."
                AND e.visible IS TRUE
            "
        );
        $stmt->assignId('group_id', $group_id);
        if ($tag !== null) {
            $stmt->assign('tag', $tag);
        }

        return $stmt->fetchAll();
    } // function


    /**
     * Get the event with the given uid from the database.
     *
     * @api
     *
     * @param string $original_uid
     *
     * @return array
     */
    public static function getByOriginalUid($original_uid)
    {
        $stmt = self::createStatement(
            "
            SELECT
                e.id,
                e.description,
                e.title,
                e.beginning,
                (e.beginning + e.length) AS ending,
                e.locality,
                e.user_id,
                e.group_id,
                e.creation,
                e.public,
                e.tags,
                e.repeat,
                e.modify
            FROM ".self::$TABLE." e
            WHERE
                e.original_uid=:original_uid
                AND visible IS TRUE
                AND disabled IS FALSE
            "
        );
        $stmt->assign('original_uid', $original_uid);

        return $stmt->fetch();
    } // function


    /**
     * Returns all events in the given timespan from the given groups.  From all of the given groups where the user,
     * given by its ID, is a member, private events will also be returned.
     *
     * @api
     *
     * @param int $start
     * @param int $end
     * @param int $user_id
     * @param array $group_ids (optional) An array containing the ID's of all groups, from which events shall be
     *        returned.
     * @param bool $public include all public events, otherwise only the matched groups are shown
     *
     * @return array
     */
    public static function getUserSpecificByTimespan($start, $end, $user_id, $group_ids = [], $public = true)
    {
        // initiate sql query
        $sql = "
            SELECT
                e.id,
                EXTRACT(EPOCH FROM e.beginning AT TIME ZONE :tzone) AS start,
                EXTRACT(EPOCH FROM (e.beginning+e.length) AT TIME ZONE :tzone) AS end,
                e.title,
                e.beginning,
                (e.beginning + e.length) AS ending,
                e.description,
                e.locality,
                u.name AS author,
                u.id AS author_id,
                g.name AS group_name,
                g.id AS group_id
            FROM view_event_only e
            JOIN tbl_user u ON u.id=e.user_id
            LEFT JOIN tbl_group g ON e.group_id=g.id
            WHERE
              (e.beginning BETWEEN to_timestamp(:start) AND to_timestamp(:end))
              AND (".($public ? "e.public IS TRUE" : "FALSE");

        // run through all subscribed group_id's but the last one
        foreach ($group_ids as $i => $group_id) {

            if (Group::isUserMember($user_id, $group_id, false)) {
                $sql .= " OR g.id=:group_id".$i;
            }
        } // foreach group ids
        $sql .= ") ORDER BY beginning";

        $stmt = self::createStatement($sql);
        $stmt->assignInt('start', $start);
        $stmt->assignInt('end', $end);
        $stmt->assign('tzone', date('T'));
        foreach ($group_ids as $i => $group_id) {
            if (Group::isUserMember($user_id, $group_id, false)) {
                $stmt->assignId('group_id'.$i, $group_id);
            }
        } // foreach group_ids

        return $stmt->fetchAll();
    } // function


    /**
     * Count all events which a user owns.
     *
     * @api
     *
     * @param int $user_id
     * @param bool $public_only (optional)
     *
     * @return int
     */
    public static function getCountByUserId($user_id, $public_only = true)
    {
        $stmt = self::createStatement(
            "
            SELECT count(*)
            FROM ".self::$TABLE."
            WHERE user_id = :user_id" . ($public_only ? " AND private IS FALSE" : "")
        );
        $stmt->assignId('user_id', $user_id);

        return $stmt->fetchColumn();
    } // function


    /**
     * Replace a user id with another user id.
     *
     * @api
     *
     * @param int $user_id
     * @param int $replacing_user_id
     * @param boolean $public_only
     *
     * @return array
     */
    public static function replaceUserId($user_id, $replacing_user_id, $public_only = true)
    {
        $stmt = self::createStatement(
            "
            UPDATE ".self::$TABLE."
            SET user_id = :replacing_user_id
            WHERE user_id = :user_id" . ($public_only ? " AND private IS FALSE" : "")
        );
        $stmt->assignId('user_id', $user_id);
        $stmt->assignId('replacing_user_id', $replacing_user_id);

        return $stmt->fetch();
    } // function


    /**
     * Returns a limited number of announces of events that are currently running or scheduled to a point in the
     * future, that are selected by random. and second dimension contains resultfields) or if no results are given a
     * empty array is returned.
     *
     * @api
     *
     * @param int $limit (optional)
     *
     * @return array you get a 2-Dimensional array (first dimension for resultsets
     */
    public static function getRandomAnnounces($limit = 15)
    {
        $stmt = self::createStatement(
            "
            SELECT *
            FROM (
                SELECT
                    a.id,
                    a.title,
                    a.description,
                    a.creation,
                    a.locality,
                    u.name AS author,
                    a.user_id,
                    a.beginning,
                    (a.beginning + a.length) AS ending,
                    (SELECT count(id) FROM tbl_comment_event WHERE event_id=a.id) AS comments
                FROM ".self::$TABLE." a
                JOIN tbl_event_announce o ON o.event_id=a.id
                JOIN tbl_user u ON u.id=a.user_id
                WHERE NOW() BETWEEN o.beginning AND o.ending
                ORDER BY RANDOM()
                LIMIT :limit) x
            ORDER BY x.beginning DESC
            "
        );
        $stmt->assignInt0('limit', $limit);

        return $stmt->fetchAll();
    } // function


    /**
     * Returns the given amount of announces.
     *
     * @api
     *
     * @param int $limit (optional) The number of announces to retrieve.
     * @param int $offset (optional) The amount of announces to be skipped before retrieving
     *
     * @return array|bool If no error occurred, an array containing the annouces will be returned, else false.
     */
    public static function getUpcoming($limit = 15, $offset = 0)
    {
        $stmt = self::createStatement(
            "
            SELECT
                a.id,
                a.title,
                a.description,
                a.creation,
                a.group_id,
                u.name AS author,
                a.user_id,
                a.locality,
                a.beginning,
                (a.beginning + a.length) AS ending,
                f.sid AS flyer_sid,
                f.description AS flyer_description,
                f.name AS flyer_name,
                (SELECT count(id) FROM tbl_comment_event WHERE event_id=a.id) AS comments
            FROM view_event_only a
            LEFT JOIN tbl_user u ON u.id=a.user_id
            LEFT JOIN tbl_file_event f ON f.event_id=a.id
            WHERE a.beginning > now()
            ORDER BY a.creation DESC
            LIMIT :limit
            OFFSET :offset
            "
        );
        $stmt->assignInt0('offset', $limit * $offset);
        $stmt->assignInt0('limit', $limit);

        return $stmt->fetchAll();
    } // function


    /**
     * Get number of announcements in the database.
     *
     * @api
     *
     * @return int The number of matching announcements.
     */
    public static function getUpcomingCount()
    {
        $stmt = self::createStatement(
            "
            SELECT count(*)
            FROM view_event_only
            WHERE beginning > now()
            "
        );

        return $stmt->fetchInt();
    } // function


    /**
     * Stores the given rating of the user with the given id for the announcement
     * with the given id.
     *
     * @api
     *
     * @param int $event_id
     * @param int $user_id
     * @param int $rating
     *
     * @return bool Returns true if no error occurred, else an exception will be thrown.
     */
    public static function addRating($event_id, $user_id, $rating)
    {
        $input = [
            'event_id' => $event_id,
            'user_id' => $user_id,
            'rating' => $rating,
            'rating_value' => $rating !== Rating::LIKE && $rating !== Rating::WAYNE
        ];

        self::getValidator($input)
            ->isNoId('event_id', 'Veranstaltungs Id ist ungültig.')
            ->isNoId('user_id', 'User Id ist ungültig.')
            ->isTrue('rating_value', 'Es wurde eine ungültige Bewertung abgegeben.')
            ->validate();

        $stmt = self::createStatement(
            "
            INSERT INTO tbl_rating_event (event_id, user_id, rate)
            VALUES (
                :event_id,
                :user_id,
                :rating)
            "
        );
        $stmt->assignId('event_id', $event_id);
        $stmt->assignId('user_id', $user_id);
        $stmt->assignInt('rating', $rating);

        return $stmt->affected();
    } // function


    /**
     * Checks if the user with the given id added a rating for the event with the given id and returns the value if
     * existing.
     *
     * @param int $event_id
     * @param int $user_id
     * @return int|false If existing, this method returns the rating value, else false.
     */
    public static function getUserRating($event_id, $user_id)
    {
        $stmt = self::createStatement(
            "
            SELECT rate
            FROM tbl_rating_event
            WHERE
                user_id=:user_id
                AND event_id=:event_id
            "
        );
        $stmt->assignId('user_id', $user_id);
        $stmt->assignId('event_id', $event_id);

        return $stmt->fetchInt();
    } // function


    /**
     * Returns a number indicating how many ratings with the given value were given for the event.
     *
     * @param int $event_id
     * @param int $rating
     * @return int The number of matching ratings.
     */
    public static function getRating($event_id, $rating)
    {
        $stmt = self::createStatement(
            "
            SELECT COUNT(*)
            FROM tbl_rating_event
            WHERE
                event_id=:event_id
                AND rate=:rating
            "
        );
        $stmt->assignId('event_id', $event_id);
        $stmt->assignInt('rating', $rating);

        return $stmt->fetchColumn();
    } // function
}// class
