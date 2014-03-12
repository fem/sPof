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
 * Interface to handle authorizations methods.
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
interface AuthorizationHandler
{

    /**
     * Handle context initialization
     *
     * @api
     *
     * @param array $context
     */
    public function handleContext(array &$context);


    /**
     * Handle permission questions.
     *
     * @api
     *
     * @param string $permission
     * @param array $context
     * @param string $environment
     *
     * @return bool
     */
    public function handlePermission(&$permission, array $context, $environment);


    /**
     * Handle privacy group questions.
     *
     * @api
     *
     * @param string $privacy
     * @param array $context
     *
     * @return bool
     */
    public function handlePrivacy($privacy, array $context);
}// class
