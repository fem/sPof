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

namespace FeM\sPof;

/**
 * Class to check parameters for valid values (depends on array passed to constructor with the values to check
 * against). To use this class, first create a new instance using constructor and the use the is* checks for the
 * index-keys you want to validate. Finally call validate(), which will throw a InvalidParameterException if at least
 * one check failed.
 *
 * Any is* method checks against cases which should not happen, so the error message for e.g. isEmpty() is added if the
 * referenced data is empty (because there is data expected). If the checked data is not present in the $input, then the
 * check is skipped without any message.
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
class InvalidParameterCheck
{

    /**
     * Regex for a acceptable password.
     */
    const REGEX_ACCEPTABLE_PASSWORD = '/^.{6,}$/';


    /**
     * Exception which contains the failed conditions. Holds name and description keys to get origin of the invalidity.
     *
     * @internal
     *
     * @var exception\InvalidParameterException
     */
    private $exception;


    /**
     * contains current input data which was passed to the constructor. Any check is done based on this data.
     *
     * @internal
     *
     * @var array
     */
    private $input = [];


    /**
     * Constructs the class with the given values (should have name => value) where name is the index used to check the
     * single conditions and value is the value to check against.
     *
     * @api
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->input = $input;
        $this->exception = new exception\InvalidParameterException();
    } // constructor


    /**
     * Simple interface to add entries to invalid parameter list.
     *
     * @internal
     *
     * @param string $name related index name (from $input)
     * @param string $description Error description displayed to the user
     */
    protected function add($name, $description)
    {
        $this->exception->addParameter($name, $description);
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name is empty.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isEmpty($name, $description)
    {
        if (isset($this->input[$name]) && empty($this->input[$name])) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name is equal to the given $value (typesafe check is used).
     *
     * @api
     *
     * @param string $name index name of $input
     * @param mixed $value value to check against
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isEqual($name, $value, $description)
    {
        if (isset($this->input[$name]) && self::escape($this->input[$name]) === self::escape($value)) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name is _not_ equal to the given $value (typesafe check is
     * used).
     *
     * @api
     *
     * @param string $name index name of $input
     * @param mixed $value value to check against
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isNotEqual($name, $value, $description)
    {
        if (isset($this->input[$name]) && self::escape($this->input[$name]) !== self::escape($value)) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name is _not_ a real boolean true or false.
     *
     * @param string $name index name of $input
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isNotBool($name, $description)
    {
        if (isset($this->input[$name]) && is_bool($this->input[$name]) === false) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name less or equal to the $value.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param mixed $value value to check against
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isLte($name, $value, $description)
    {
        if (isset($this->input[$name]) && self::escape($this->input[$name]) <= self::escape($value)) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name can't be used as Id.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isNoId($name, $description)
    {
        $this->isLte($name, 0, $description);

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name greater than $value.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param mixed $value value to check against
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isGt($name, $value, $description)
    {
        if (isset($this->input[$name]) && self::escape($this->input[$name]) > self::escape($value)) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name less than $value.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param mixed $value value to check against
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isLt($name, $value, $description)
    {
        if (isset($this->input[$name]) && self::escape($this->input[$name]) < self::escape($value)) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if length of the value indexed by $name greater than $length.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param int $length value to check against
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isLengthGt($name, $length, $description)
    {
        if (isset($this->input[$name]) && strlen($this->input[$name]) > $length) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if length of the value indexed by $name lesser than $length.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param int $length value to check against
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isLengthLt($name, $length, $description)
    {
        if (isset($this->input[$name]) && strlen($this->input[$name]) < $length) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name is true (non-typesafe check is used).
     *
     * @api
     *
     * @param string $name index name of $input
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isTrue($name, $description)
    {
        if (isset($this->input[$name]) && $this->input[$name] == true) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name is false (non-typesafe check is used).
     *
     * @api
     *
     * @param string $name index name of $input
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isFalse($name, $description)
    {
        if (isset($this->input[$name]) && $this->input[$name] == false) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name no valid email adress.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isNoEmail($name, $description)
    {
        if (isset($this->input[$name]) && (filter_var($this->input[$name], FILTER_VALIDATE_EMAIL) === false)) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name no valid url.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function isNoUrl($name, $description)
    {
        if (isset($this->input[$name]) && (filter_var($this->input[$name], FILTER_VALIDATE_URL) === false)) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Add entry to invalid parameters if value indexed by $name does not match the given regular expression.
     *
     * @api
     *
     * @param string $name index name of $input
     * @param string $regex regular expression to check against
     * @param string $description Error description displayed to the user
     *
     * @return $this
     */
    public function byRegex($name, $regex, $description)
    {
        if (isset($this->input[$name]) && (preg_match($regex, $this->input[$name]) == 0)) {
            $this->add($name, $description);
        }

        return $this;
    } // function


    /**
     * Throws an exception if any parameter failed at least one condition. Does nothing if everything went fine.
     *
     * @api
     *
     * @throws exception\InvalidParameterException if any operation failed
     */
    public function validate()
    {
        if ($this->exception->getFailCount()) {
            throw $this->exception;
        }
    } // function


    /**
     * Make sure values can be compared.
     *
     * @internal
     *
     * @param mixed $value
     * @return string
     */
    private function escape($value)
    {
        if ($value instanceof \DateTime) {
            $value = $value->format(\DateTime::ISO8601);
        }
        return $value;
    } // function
}// class
