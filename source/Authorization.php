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
 * Class to check if a user has permissions to do $things, Based on the owner user, the owner group and the privacy
 * settings.  The owner user is always the user which originally created the entry. The owner group is always the
 * group in which the entry was originally created. The context may contain additional settings.
 *
 * @package FeM\sPof
 * @author dangerground
 * @author pegro
 * @since 1.0
 */
class Authorization
{
    /**
     * "Cached" variable to keep the current login status of the requesting user.
     *
     * @internal
     *
     * @var bool
     */
    private $isLoggedIn = false;

    /**
     * Holds the global context for the current instance, e.g. with owner user or owner group.
     *
     * @internal
     *
     * @var array
     */
    private $context = [];

    /**
     * Holds current global environment string.
     *
     * @internal
     *
     * @var string
     */
    private $environment = null;


    /**
     * Private constructor. Ensures there is always a owner group and user.
     *
     * @internal
     *
     * @param string  $environment (optional)
     * @param array   $context
     */
    private function __construct($environment = '', array $context = [])
    {
        $this->setEnvironment($environment, $context);

        $this->handlers = Application::getAuthorizationHandlers();
        foreach ($this->handlers as $handler) {
            $handler->handleContext($this->context);
        }

        // make sure, we always have user and group id of the owner
        if (!isset($this->context['request_user_id'])) {
            $this->context['request_user_id'] = Session::getUserId();
        }
        if (isset($this->context['request_user_id'])) {
            $this->isLoggedIn = $this->context['request_user_id'] > 0;
        }
        if (!isset($this->context['owner_user_id'])) {
            $this->context['owner_user_id'] = -1;
        }
        if (!isset($this->context['owner_group_id'])) {
            $this->context['owner_group_id'] = -1;
        }
    } // constructor


    /**
     * Enforce one instance per environment.
     *
     * @api
     *
     * @param string $environment (optional)
     * @param array $context (optional)
     * @return Authorization
     */
    public static function getInstance($environment = '', array $context = [])
    {
        static $instance = [];

        // serialize context and environment to get a "unique" instance
        $name = serialize($environment).serialize($context);
        if (!isset($instance[$name])) {
            $instance[$name] = new self($environment, $context);
        }
        return $instance[$name];
    } // function


    /**
     * Copy environment and context to class properties.
     *
     * To empty the context provide empty array as input parameter. To set the environment only omit the second
     * parameter.
     *
     * @internal
     *
     * @param string  $environment
     * @param array   $context
     */
    private function setEnvironment($environment, array $context)
    {
        if (!empty($environment)) {
            $this->environment = trim($environment, '.').'.';
        }
        $this->context = $context;

        if (isset($this->context['request_user_id'])) {
            $this->isLoggedIn = $this->context['request_user_id'] > 0;
        }
    } // function


    /**
     * Same as hasPermission, but throws a NotAuthorizedException if no permissio is given. Can be used to enforce a
     * required permission for a whole method. Should be used as early as possible in the scope (e.g. method).
     *
     * @api
     *
     * @throws exception\NotAuthorizedException if permission isn't granted
     *
     * @param string $permission
     * @param array $context
     */
    public function requires($permission, array $context = [])
    {
        if (isset($context['request_user_id'])) {
            $this->isLoggedIn = $context['request_user_id'] > 0;
        }

        if (!$this->hasPermission($permission, $context, true)) {
            throw new exception\NotAuthorizedException(
                _s('Du hast nicht die benötigten Rechte, um die Funktion nutzen zu können.')
                .(!$this->isLoggedIn ? '<br /> '._s('Vielleicht hast du vergessen dich einzuloggen?') : '')
            );
        }
    } // function


    /**
     * Check for group-permissions -> check for privacy settings -> or use default privacy settings. Can be used
     * anywhere to check permission for e.g. a specific item. To exclude users from using the whole scope, requires()
     * should be used instead.
     *
     * @api
     *
     * @throws exception\NotLoggedInException
     *
     * @param string $permission
     * @param array $context (optional)
     * @param bool $throwing (optional) should the function throw exceptions itself
     *
     * @return bool
     */
    public function hasPermission($permission, array $context = [], $throwing = false)
    {
        if (isset($context['request_user_id'])) {
            $this->isLoggedIn = $context['request_user_id'] > 0;
        }

        // mix local and environmental context
        $context = array_merge($this->context, $context);

        foreach ($this->handlers as $handler) {
            if ($handler->handlePermission($permission, $context, $this->environment)) {
                return true;
            }
        }

        // get privacy settings from user (fallback: get default setting)
        $privacy = model\Permission::getPrivacyGroup($permission, $context['owner_user_id']);

        // handle permission by privacy settings
        $result =  $this->checkByPrivacy($privacy, $context);

        // details usefull for debugging
        $debug_msg = $permission.'" '.($result == true ? '' : 'not ').'granted';
        // warn, if permission has no default permission group assigned
        if (empty($privacy)) {
            $debug_msg .= ', reason: no default privacy group';
        } else {
            $debug_msg .= ', reason: "'.$privacy.'"=='.($result ? "true" : "false");
        }
        Logger::getInstance()->auth($debug_msg);

        // handle per account permissions
        if ($result === false && $this->isLoggedIn) {
            $result = model\Permission::isApplied($context['request_user_id'], $context['owner_group_id'], $permission);
            $debug_msg = $permission.'" '.($result == true ? '' : 'not ').'granted explicitly';
            Logger::getInstance()->auth($debug_msg);
        }


        // get special case of 'just not being logged in'
        if ($throwing && $privacy === 'logged in' && $result === false) {
            throw new exception\NotLoggedInException(_s('Du musst eingeloggt sein, um diesen Bereich nutzen zu können.'));
        }

        return $result;
    } // function hasPermission


    /**
     * Check permissions by privacy group.
     *
     * @internal
     *
     * @param string $privacy privacy name
     * @param array $context
     *
     * @return bool
     */
    private function checkByPrivacy($privacy, array $context)
    {
        foreach ($this->handlers as $handler) {
            if ($handler->handlePrivacy($privacy, $context)) {
                return true;
            }
        }

        switch ($privacy) {

            case 'anybody':
                // valid for everyone
                // @codingStandardsIgnoreStart
                return true;
                // @codingStandardsIgnoreEnd

            case 'logged in':
                // check if the user is logged in, other conditions do not match if the
                // user is not logged in -> in that case: fail
                // @codingStandardsIgnoreStart
                return $this->isLoggedIn;
                // @codingStandardsIgnoreEnd
                // no break

            case 'in group':
                // Checks if the requesting user and the owner user are sharing groups
                if (model\Group::isUserMember($context['request_user_id'], $context['owner_group_id'], false)) {
                    return true;
                }
                // no break

            case 'sharing groups':
                // Checks if the requesting user and the owner user are sharing groups
                $groups = model\Group::getByUserId($context['owner_user_id']);

                foreach ($groups as $group) {
                    if (model\Group::isUserMember($context['request_user_id'], $group['id'], false)) {
                        return true;
                    }
                }
                // no break

            case 'owner':
                // check if request user and owner user are identical
                if ($context['request_user_id'] !== null && $context['request_user_id'] == $context['owner_user_id']) {
                    return true;
                }
        } // end switch privacy
        return false;
    } // function
}// class
