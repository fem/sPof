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
use FeM\sPof\exception\NotImplementedException;
use FeM\sPof\exception\AbstractMethodException;
use FeM\sPof\exception\InvalidParameterException;
use FeM\sPof\Logger;

/**
 * This class contains all common static functions of every object model class with a associated table.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @author mbyr
 * @since 1.0
 */
abstract class AbstractModel
{
    /**
     * If true no 'id' field will be returned by the add method.
     *
     * @internal only used in abstractmodel with id
     *
     * @var bool
     */
    protected static $RETURN_PRIMARY_KEY = false;

    /**
     * Reference to the current table in the model.
     *
     * @api
     *
     * @var string
     */
    protected static $TABLE = null;


    /**
     * Checks the given array for valid key-value-combinations.
     *
     * @api
     *
     * @throws \FeM\sPof\exception\AbstractMethodException if implementation of this method is missing in the model

     * @param array $input
     */
    public static function isValid(array $input)
    {
        static::validate($input);
    } // function


    /**
     * Checks the given array for valid key-value-combinations.
     *
     * @api
     *
     * @throws \FeM\sPof\exception\AbstractMethodException if implementation of this method is missing in the model

     * @param array $input
     */
    protected static function validate(array $input)
    {
        // php forbids abstract static functions, so throw an exception instead
        throw new AbstractMethodException(_s('An abstract validate was called with ').var_export($input, true));
    } // function


    /**
     * Returns the name of the table that should be used for the next transaction. correct table if needed. The add-
     * and updateByPk-Method put their given col-value-pairs into this parameter. If this parameter shall impact the
     * return value this function needs to be overwritten.
     *
     * @api
     *
     * @throws \FeM\sPof\exception\NotImplementedException in case of missing $TABLE definition
     *
     * @return string
     */
    protected static function getTable()
    {
        if (static::$TABLE === null) {
            throw new NotImplementedException(_s('Missing table definition in Model ').get_called_class());
        }

        return static::$TABLE;
    } // function


    /**
     * Insert data into the database according to the given value array. This array must have a special form
     * key:   Name of the target column
     * value: The value to be inserted
     *
     * @api
     *
     * @param array $input
     *
     * @return int The id of the newly inserted object.
     *
     * @throws ModelException thrown by self::execute() on error
     */
    public static function add(array $input)
    {
        static::validate($input);

        // Prepare statement and bind values
        $stmt = self::createStatement(
            "
            INSERT INTO ".static::getTable($input)." (\"".implode('","', array_keys($input))."\")
            VALUES (
                :".implode(',:', array_keys($input)).")".(static::$RETURN_PRIMARY_KEY ?
                "RETURNING \"id\"" : '')
        );
        foreach ($input as $key => &$value) {
            $stmt->assignGuessType($key, $value);
        }

        if (static::$RETURN_PRIMARY_KEY) {
            return $stmt->fetchInt();
        } else {
            return $stmt->execute();
        }
    } // function


    /**
     * Returns an array containing data related to the item with the given id and type.
     *
     * @api
     *
     * @param mixed $primary_key a single value represents a single primary key, if the primary key contains more than
     *        one column you need to specify a key-value-array with keys containing the column name and value the
     *        queried value
     * @param bool $visible_only (optional) show only visible entries
     *
     * @return array|false An array containing information about the item with the given, false when problems entry
     *         does not exist
     */
    public static function getByPk($primary_key, $visible_only = true)
    {
        $sql = "
            SELECT *
            FROM ".static::getTable()."
            WHERE
                \"disabled\" IS FALSE
                ".($visible_only ? "AND \"visible\" IS TRUE" : '').
                " AND ";

        // build query and add values from all primary key columns
        $condition = static::buildConditionByPk($primary_key);

        $stmt = DBConnection::getInstance()->prepare($sql.$condition['sql']);
        foreach ($condition['params'] as $key => &$value) {
            $stmt->assignGuessType($key, $value);
        }

        return $stmt->fetch();
    } // function


    /**
     * Deletes an object from the database.
     *
     * @api
     *
     * @param mixed $primary_key
     *
     * @return bool True if the object was deleted successfully, else false.
     */
    public static function deleteByPk($primary_key)
    {
        $sql = "
            DELETE
            FROM ".static::getTable()."
            WHERE ";

        // build query and add values from all primary key columns
        $condition = static::buildConditionByPk($primary_key);
        $stmt = self::createStatement($sql.$condition['sql']);
        foreach ($condition['params'] as $key => $value) {
            $stmt->assignGuessType($key, $value);
        }

        return $stmt->affected();
    } // function


    /**
     * Assigns new values to an object existing in the table with the given name. The given array must have the same
     * form like described in add()
     *
     * @api
     *
     * @param mixed $primary_key
     * @param array $input
     *
     * @return bool True if the object was updated successfully, else false.
     */
    public static function updateByPk($primary_key, array $input)
    {
        static::validate($input);

        $sql = "
            UPDATE ".static::getTable($input)."
            SET ".self::combineValues(array_keys($input), ' = :', ',')."
            WHERE ";


        // build query and add values from all primary key columns
        $condition = static::buildConditionByPk($primary_key);
        $stmt = self::createStatement($sql.$condition['sql']);

        // bind where condition
        foreach ($condition['params'] as $key => &$value) {
            $stmt->assignGuessType($key, $value);
        }

        // bind update values
        foreach ($input as $key => &$value) {
            $stmt->assignGuessType($key, $value);
        }

        return $stmt->affected();
    } // function


    /**
     * Combine a value to the form "entry1 = :entry1,..", where "," is only applied between different entries and not
     * as a last character, the "= :" is only applied between two entries. @see self::updateByPk() .
     *
     * @internal
     *
     * @param array $source
     * @param string $column_char (optional) char to combine between keys
     * @param string $row_char (optional) char to combine the combined keys
     *
     * @return string
     */
    private static function combineValues(array $source, $column_char = '= :', $row_char = ',')
    {
        $result = [];
        foreach ($source as $row) {
            $result[] = '"'.$row.'"'.$column_char.$row;
        }
        return implode($row_char, $result);
    } // function


    /**
     * Build sql query for where conditions based on the given primary key.
     *
     * @internal
     *
     * @param array $primary_key array with key-value-array
     * @return array with sql and params keys for integration into where condition and binding params
     */
    protected static function buildConditionByPk($primary_key)
    {
        if (!is_array($primary_key)) {
            throw new InvalidParameterException(
                _s('Primary Key is no array, maybe you wanted to use AbstractModelWithId instead?')
            );
        }

        $sql = " (";

        // add each column to query
        foreach (array_keys($primary_key) as $i => $key) {
            if ($i > 0) {
                $sql .= " AND ";
            }
            $sql .= "\"".$key."\" = :".$key;
        }
        $sql .= ") ";

        return [
            'sql' => $sql,
            'params' => $primary_key
            ];
    } // function


    /**
     * Returns a new prepared Statement. Assign values to it and fetch the results in the end.
     *
     * @api
     *
     * @param string $statement sql query with params
     *
     * @return \FeM\sPof\model\DBStatement
     */
    final protected static function createStatement($statement)
    {
        return DBConnection::getInstance()->prepare($statement);
    } // function


    /**
     * Returns the current validator class. Hardcoded to InvalidParameterCheck at the moment, but at least this makes
     * the models independent from importing the class.
     *
     * @api
     *
     * @param array $input array key-value pairs, where keys are the database fields and value the designated value to
     *        put in the database
     *
     * @return \FeM\sPof\InvalidParameterCheck
     */
    final protected static function getValidator(array $input)
    {
        return new \FeM\sPof\InvalidParameterCheck($input);
    } // function
}// class
