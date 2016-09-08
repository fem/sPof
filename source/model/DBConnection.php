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
        'check_alive_time' => 120
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
        static $time_last_use;
        static $instance;
        if (!isset($instance)) {
            static $connection_try;
            if (!$connection_try) {
                $instance = new self();
                $connection_try = true;
                $time_last_use = time();
            }
        } else {
            // if database handle wasn't used for some time, check if connection is still alive
            if($time_last_use < time() - self::$defaultConfig['check_alive_time']) {
                try {
                    $instance->query('SELECT 1');
                } catch(\PDOException $e) {
                    Logger::getInstance()->info('Database connection got lost. Try reconnecting...');

                    $instance = new self();
                }
            }
            $time_last_use = time();
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
