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

use FeM\sPof\Config;
use FeM\sPof\Logger;

/**
 * Represent the one and only database connection. Always use the database through and instance of this object.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
class DBConnection extends \PDO
{

    /**
     * Default configuration for this class.
     *
     * @api
     *
     * @var array
     */
    private static $defaultConfig = [
        'user' => 'exampleUser',
        'pass' => 'examplePassword',
        'name' => 'exampleDatabase',
        'namespace' => 'public',
        'host' => 'localhost',
        'port' => 5432,
        'debug' => false,
    ];


    /**
     * Get the instance of database connection.
     *
     * @api
     *
     * @return DBConnection
     */
    public static function getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            static $connection_try;
            if (!$connection_try) {
                // does not work with
                #if (Config::get('debug', 'database')) {
                #    $instance = new TraceablePDO(new DBConnection());
                #    Logger::getInstance()->debugBar->addCollector(new PDOCollector($instance));
                #} else {
                    $instance = new self();
                #}
                $connection_try = true;
            }
        }
        return $instance;
    } // function


    /**
     * Check if a database connection can be established.
     *
     * @api
     *
     * @return bool
     */
    public static function isOnline()
    {
        return self::getInstance() !== null;
    } // function


    /**
     * Create a new database connection.
     *
     * @internal
     */
    public function __construct()
    {
        try {
            $config = array_merge(self::$defaultConfig, Config::get('database'));

            parent::__construct(
                'pgsql:host='.$config['host'].';'
                .'dbname='.$config['name'].';'
                .'port='.$config['port'],
                $config['user'],
                $config['pass']
            );
#            Config::unsetForDatabase();

            $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [__NAMESPACE__.'\\DBStatement', [$this]]);
            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->exec("SET CLIENT_ENCODING TO 'UTF8'");
            $this->exec("SET search_path TO ".$config['namespace'].";");
        } catch (\PDOException $e) {
            Logger::getInstance()->exception($e);
        }
    } // constructor


    /**
     * Start a new database transaction.
     *
     * @api
     *
     * @return bool
     */
    public function beginTransaction()
    {
        Logger::getInstance()->traceStart('transaction');
        return parent::beginTransaction();
    } // function


    /**
     * Stop the current transaction and commit the changes.
     *
     * @api
     *
     * @return bool
     */
    public function commit()
    {
        Logger::getInstance()->traceStop('transaction');
        return parent::commit();
    } // function


    /**
     * Stop the current transactions and rollback all changes.
     *
     * @api
     *
     * @return bool
     */
    public function rollBack()
    {
        Logger::getInstance()->traceStop('transaction');
        return parent::rollBack();
    } // function
}// class
