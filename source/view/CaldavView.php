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

use FeM\sPof\dav\AuthBackend;
use FeM\sPof\dav\CalendarBackend;
use FeM\sPof\dav\PrincipalBackend;


/**
 * Handle all CalDAV requests.
 *
 * @package FeM\sPof\view
 * @author dangerground
 * @since 1.0
 */
class CaldavView extends AbstractRawView
{
    /**
     * Show everything we have to show...
     */
    public function show()
    {
        // Backends
        $authBackend = new AuthBackend();
        $calendarBackend = new CalendarBackend();
        $principalBackend = new PrincipalBackend();

        // Directory structure
        $tree = [
            new \Sabre\CalDAV\Principal\Collection($principalBackend),
            new \Sabre\CalDAV\CalendarRootNode($principalBackend, $calendarBackend),
        ];

        $config = \FeM\sPof\Config::get('server');

        $server = new \Sabre\DAV\Server($tree);
        //$server->setBaseUri();

        /* Server Plugins */
        $authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend, 'SabreDAV');
        $server->addPlugin($authPlugin);

        /* CalDAV support */
        $caldavPlugin = new \Sabre\CalDAV\Plugin();
        $server->addPlugin($caldavPlugin);


        // Support for html frontend
        $browser = new \Sabre\DAV\Browser\Plugin();
        $server->addPlugin($browser);

        $aclPlugin = new \Sabre\DAVACL\Plugin();
        $server->addPlugin($aclPlugin);

        // And off we go!
        $server->exec();
        exit;
    } // function
}// class
