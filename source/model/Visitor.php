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

/**
 * Visitor repository.
 *
 * @package FeM\sPof\model
 * @author deka
 * @since 1.0
 */
abstract class Visitor extends AbstractModel
{

    /**
     * Referenced table name.
     *
     * @internal
     *
     * @var string
     */
    public static $TABLE = 'tbl_statistic_visitor';


    /**
     * Get minimum number of visitors for a day.
     *
     * @api
     *
     * @return int
     */
    public static function getMin()
    {
        $stmt = self::createStatement(
            "
            SELECT min(count)
            FROM tbl_statistic_visitor
            "
        );

        return $stmt->fetchColumn();
    } // function


    /**
     * Get maximum number of visitors for a day.
     *
     * @api
     *
     * @return int
     */
    public static function getMax()
    {
        $stmt = self::createStatement(
            "
            SELECT max(count)
            FROM tbl_statistic_visitor
            "
        );

        return $stmt->fetchColumn();
    } // function


    /**
     * Get the list of visitors for the last few days.
     *
     * @api
     *
     * @param int $limit (optional) number of last days to request
     *
     * @return array
     */
    public static function getLatest($limit = 30)
    {
        $stmt = self::createStatement(
            "
            SELECT
                count,
                date
            FROM tbl_statistic_visitor
            ORDER BY date DESC
            LIMIT :limit
            "
        );
        $stmt->assignInt('limit', $limit);

        return $stmt->fetchAll();
    } // function


    /**
     * Delete all visitor host that are older than one hour
     *
     * @internal
     *
     * @return bool
     */
    public static function clear()
    {
        $stmt = self::createStatement(
            "
            DELETE
            FROM tbl_statistic_visitor_host
            WHERE modify < CURRENT_TIMESTAMP - INTERVAL '1 HOUR'
            "
        );

        return $stmt->affected();
    } // function


    /**
     * Check if a visitor is recognized.
     *
     * @internal
     *
     * @param bool $ipAddress
     *
     * @return bool
     */
    public static function isRegistered($ipAddress)
    {
        $stmt = self::createStatement(
            "
            SELECT TRUE
            FROM tbl_statistic_visitor_host
            WHERE ip=:ipAddress
            "
        );
        $stmt->assign('ipAddress', $ipAddress);

        return $stmt->fetchColumn() !== false;
    } // function


    /**
     * Add a new visitor. Register and update counter.
     *
     * @internal
     *
     * @param array $input
     * @return void
     */
    public static function add(array $input)
    {
        DBConnection::getInstance()->beginTransaction();
        $stmt = self::createStatement(
            "
            UPDATE tbl_statistic_visitor
            SET count=count+1
            WHERE date=CURRENT_DATE
            "
        );
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $stmt = self::createStatement(
                "
                INSERT INTO tbl_statistic_visitor (date)
                VALUES(CURRENT_DATE)
                "
            );
            $stmt->execute();
        }

        $stmt = self::createStatement(
            "
            INSERT INTO tbl_statistic_visitor_host (ip)
            VALUES (:ipAddress)
            "
        );
        $stmt->assign('ipAddress', $input['ip']);
        $stmt->execute();
        DBConnection::getInstance()->commit();
        DBConnection::getInstance()->rollBack();
    } // function


    /**
     * Update last action of a visitor.
     *
     * @internal
     *
     * @param string $ipAddress
     *
     * @return bool
     */
    public static function touch($ipAddress)
    {
        $stmt = self::createStatement(
            "
            UPDATE tbl_statistic_visitor_host
            SET modify=NOW()
            WHERE ip=:ipAddress
            "
        );
        $stmt->assign('ipAddress', $ipAddress);

        return $stmt->affected();
    } // function
}// class
