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

use FeM\sPof\exception\ModelException;
use FeM\sPof\exception\UnexpectedIntegrityConstraintViolationException;
use FeM\sPof\Logger;

/**
 * Represent the current Database statement which is build and where data can be fetched from.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @author deka
 * @author pegro
 * @since 1.0
 */
class DBStatement extends \PDOStatement
{
    /**
     * Instance to parent pdo object
     *
     * @internal
     *
     * @var \PDO
     */
    private $pdo;

    /**
     * Marks if the query was invalid. If true the query should not get executed. It also indicated to return the
     * default on-error value.
     *
     * @internal
     *
     * @var bool
     */
    private $invalid = false;

    /**
     * If the query was marked invalid, because of an Id. The id is hold here.
     *
     * @internal
     *
     * @var string
     */
    private $invalidId;


    /**
     * Set local pdo reference. Gets automatically called on construction from DBConnection.
     *
     * @internal
     *
     * @param \PDO $pdo
     */
    protected function __construct($pdo)
    {
        $this->pdo = $pdo;
    } // constructor


    /**
     * Wrap up the execution of an statement execution. Logs errors and throws exceptions. Should not be used by
     * external calls as fetch* or affected always call an execute. So unless you want multiple executions of a
     * statement don't use this function.
     *
     * @internal
     *
     * @param array $bound_input_params (ignored)
     * @throws \FeM\sPof\exception\ModelException if a "write" statement failed
     * @return bool
     */
    public function execute($bound_input_params = null)
    {
        if ($this->invalid) {
            // TODO announce which id/field failed the check
            return false;
        }

        try {
            Logger::getInstance()->traceStart('execute db query', $this->queryString);
            $retVar = parent::execute();
            Logger::getInstance()->traceStop('execute db query');
            return $retVar;
        } catch (\PDOException $e) {
            $this->invalid = true;

            $message = $e->getMessage();

            // add statemenet parameters to exception message
            ob_start();
            $this->debugDumpParams();
            $message .= "\n".ob_get_clean();

            // check for constraint voilations
            if ($e->getCode() >= 23000 && $e->getCode() < 24000) {
                $e = new UnexpectedIntegrityConstraintViolationException($message, 0, $e);
            } else {
                // nest exception, to include changed error message
                $e = new \PDOException($message, 0, $e);
            }

            if (strpos(ltrim($this->queryString), 'INSERT') === 0
                || strpos(ltrim($this->queryString), 'UPDATE') === 0
                || strpos(ltrim($this->queryString), 'DELETE') === 0
            ) {
                throw new ModelException($e);
            } else {
                Logger::getInstance()->exception($e);
            }
        }

        return false;
    } // function


    /**
     * Get the (first) resultset of a Statement.
     *
     * @api
     *
     * @param int $fetch_style
     * @param int $orientation (ignored)
     * @param int $offset (ignored)
     *
     * @return array|false false if no result was found or a param was invalid
     */
    public function fetch($fetch_style = \PDO::FETCH_ASSOC, $orientation = null, $offset = null)
    {
        $this->execute();
        if ($this->invalid) {
            return false;
        }

        return parent::fetch($fetch_style);
    } // function


    /**
     * Fetch the first column of a resultset.
     *
     * @api
     *
     * @param int $column_number
     *
     * @return string|false false if no result was found or a param was invalid
     */
    public function fetchColumn($column_number = 0)
    {
        $this->execute();
        if ($this->invalid) {
            return false;
        }

        return parent::fetchColumn($column_number);
    } // function


    /**
     * Like fetchColumn, but returns the value as integer.
     *
     * @api
     *
     * @param int $column_number
     * @return int|false
     */
    public function fetchInt($column_number = 0)
    {
        $this->execute();
        if ($this->invalid) {
            return false;
        }

        $value = parent::fetchColumn($column_number);
        if ($value === false) {
            return false;
        }

        return (int)$value;
    } // function


    /**
     * Returns all resultsets in a array in array fashion.
     *
     * @api
     *
     * @param int $fetch_style
     * @param string $class_name (ignored)
     * @param array $ctor_args (ignored)
     *
     * @return array on error or no results an empty array is returned
     */
    public function fetchAll($fetch_style = \PDO::FETCH_ASSOC, $class_name = null, $ctor_args = null)
    {
        $this->execute();
        if ($this->invalid || $this->rowCount() === 0) {
            return [];
        }

        return parent::fetchAll($fetch_style);
    } // function


    /**
     * Returns the number of affected rows of a query. May be useful to determine if a insert/delete/update had any
     * effect.
     *
     * @api
     *
     * @return bool
     */
    public function affected()
    {
        $this->execute();
        if ($this->invalid) {
            return false;
        }

        return parent::rowCount() > 0;
    } // function


    /**
     * Try to assign a valid type.
     *
     * @api
     *
     * @param string $name statement placeholder name
     * @param string $value
     */
    public function assignGuessType($name, $value)
    {
        if (is_bool($value)) {
            $this->assignBool($name, $value);
        } elseif ($value instanceof \DateTime) {
            $this->assignDate($name, $value);
        } elseif ($value === null) {
            $this->bindValue($name, $value, \PDO::PARAM_NULL);
        } else {
            $this->assign($name, $value);
        }
    } // function


    /**
     * Assignes a new string statement variable to the statement. You may use other assign*() methods to pass the
     * data.
     *
     * @api
     *
     * @param string $name statement placeholder name
     * @param string $value
     */
    public function assign($name, $value)
    {
        $this->bindValue($name, $value, \PDO::PARAM_STR);
    } // function


    /**
     * Assignes a new integer(id) statement variable to the statement. The value has to be greater than 0, otherwise
     * the query is marked as invalid and will never get executed. Use only if you pass an real id, for numbers use
     * assignInt().
     *
     * @api
     *
     * @param string $name statement placeholder name
     * @param int $value
     */
    public function assignId($name, $value)
    {
        if ($value <= 0) {
            $this->invalid = true;
            $this->invalidId = $name;
        }

        $this->bindValue($name, $value);
    } // function


    /**
     * Assignes a new boolean statement variable to the statement.
     *
     * @api
     *
     * @param string $name statement placeholder name
     * @param bool $value
     */
    public function assignBool($name, $value)
    {
        $this->bindValue($name, $value, \PDO::PARAM_BOOL);
    } // function


    /**
     * Assignes a new integer statement variable to the statement. If you want to pass a database id to the statement,
     * please use assignId() as it does some sanity checks before executing the query and thus save some cycles.
     *
     * @api
     *
     * @param string $name statement placeholder name
     * @param int $value
     */
    public function assignInt($name, $value)
    {
        if ($value === null) {
            $value = 0;
        }

        $this->bindValue($name, $value, \PDO::PARAM_INT);
    } // function


    /**
     * Assignes a new integer statement variable to the statement. The value has to be greater than or equal to  0,
     * otherwise the query is marked as invalid and will never get executed. For integer values in general use
     * assignInt().
     *
     * @api
     *
     * @param string $name statement placeholder name
     * @param int $value
     */
    public function assignInt0($name, $value)
    {
        if ($value < 0) {
            $this->invalid = true;
            $this->invalidId = $name;
        }

        $this->assignInt($name, $value);
    } // function


    /**
     * Assignes a new date/time statement variable to the statement.
     *
     * @api
     *
     * @param string $name statement placeholder name
     * @param \DateTime $value
     */
    public function assignDate($name, \DateTime $value)
    {
        if(empty($value->getTimezone()->getLocation())) {
            $value->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        }
        $this->assign($name, $value->format(\DateTime::ISO8601));
    } // function
}// class
