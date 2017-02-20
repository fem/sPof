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
 * This class contains all common static functions of every object model class which has an 'id' field.
 *
 * @package FeM\sPof\model
 * @author dangerground
 * @author mbyr
 * @since 1.0
 */
abstract class AbstractModelWithId extends AbstractModel
{
    /**
     * If true, the primary key will be returned from the add() method.
     *
     * @internal
     *
     * @var bool
     */
    protected static $RETURN_PRIMARY_KEY = true;


    /**
     * Name of the primary key column
     *
     * @internal
     *
     * @var string
     */
    protected static $PRIMARY_KEY_NAME = 'id';


    /**
     * @internal
     *
     * @param mixed $primary_key single column or array with key-value-array
     *
     * @return array with sql and params keys for integration into where condition and binding params
     */
    protected static function buildConditionByPk($primary_key)
    {
        // it's only one column
        return [
            'sql' => static::$PRIMARY_KEY_NAME." = :".static::$PRIMARY_KEY_NAME." ",
            'params' => [static::$PRIMARY_KEY_NAME => $primary_key]
        ];
    } // function
}// class
