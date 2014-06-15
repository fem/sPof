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

namespace FeM\sPof\view;

use FeM\sPi\model\Group;
use FeM\sPi\model\Location;
use FeM\sPi\model\User;
use FeM\sPof\Session;
use FeM\sPof\Request;

/**
 * Suggestions based on a query string.
 *
 * @package FeM\sPof\view
 * @author dangerground
 * @author deka
 * @since 1.0
 */
class RpcSuggestView extends \FeM\sPof\view\AbstractJsonView
{
    /**
     * Query string
     *
     * @internal
     *
     * @var string
     */
    protected $query;

    /**
     * Similiar query string
     *
     * @internal
     *
     * @var string
     */
    protected $querySimiliar;


    /**
     * initialize.
     *
     * @internal
     */
    public function __construct()
    {
        if (!Session::isLoggedIn()) {
            exit;
        }
        $this->query = Request::getStrParam('q', Request::getStrParam('term'));
        $this->querySimiliar = (strlen($this->query) > 1 ? $this->query.'%' : '');

        parent::__construct();
    } // constructor


    /**
     * List users by query.
     *
     * @internal
     */
    public function user()
    {
        $this->resultSet = User::searchNameBySimilarity($this->query, $this->querySimiliar);
    } // function


    /**
     * List groups by query.
     *
     * @internal
     */
    public function group()
    {
        $result = Group::searchNameBySimilarity($this->query, $this->querySimiliar);
        $this->resultSet = array_column($result, 'name');
    } // function
}// class
