<?php
/**
 * This file is part of sPof.
 *
 * FIXME license
 *
 * @copyright 2003-2014 Forschungsgemeinschaft elektronische Medien e.V. (http://fem.tu-ilmenau.de)
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
