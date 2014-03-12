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

namespace FeM\sPof\notification;

/**
 * Handler to add Notification targets for an application.
 *
 * @api
 *
 * @package FeM\sPof\notification
 * @author dangerground
 * @since 1.0
 */
interface NotificationHandler
{

    /**
     * Get name of the notification channel.
     *
     * @return string a unique name representing the protocol to
     */
    public function getType();


    /**
     * Handle the notification protocol.
     *
     * @param array $context with keys, from_user_id, to_user_id, description, content
     *
     * @return bool
     */
    public function handleNotification(array $context);
}// interface
