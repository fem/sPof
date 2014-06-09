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

use FeM\sPof\model\Notification;
use FeM\sPof\model\NotificationMessage;
use FeM\sPof\model\NotificationProtocol;
use FeM\sPof\model\LogEvent;
use FeM\sPof\model\Permission;
use FeM\sPof\model\DBConnection;

/**
 * Dispatcher for controllers.
 *
 * @package FeM\sPof
 * @author dangerground
 * @since 1.0
 */
abstract class AbstractController extends AbstractModule
{
    /**
     * if true, all Database operations are atomic
     *
     * @internal
     *
     * @var bool
     */
    private $atomic = true;

    /**
     * Current action command.
     *
     * @internal
     *
     * @var string
     */
    private $command;

    /**
     * Hold notifications (user_id and associated message)
     *
     * @internal
     *
     * @var array
     */
    private $notifications = [];

    /**
     * Return action result as JSON if this is true.
     *
     * @internal
     *
     * @var bool
     */
    protected $isJSON = false;


    /**
     * Dummy constructor, so deffered classes can always call parent.
     *
     * @internal
     *
     * @param bool $isJSON return action result as JSON if true
     */
    final public function __construct($isJSON = false)
    {
        $this->isJSON = $isJSON;
    } // constructor


    /**
     * Create controller and run Action
     *
     * @internal
     *
     * @param $module
     * @param $action
     */
    final public static function createAndRunAction($module, $action)
    {
        $ctrlName = Application::$NAMESPACE.'controller\\'.$module.'Controller';
        /** @var AbstractController $ctrl */
        $ctrl = new $ctrlName(Request::getBoolParam('resultAsJSON'));
        $ctrl->executeCommand($action);
    } // function


    /**
     * Builds transactions about command executions and catches \Exceptions, set error messages and redirects to
     * bypass form re-submit of data. Specific actions are defined in handleCommand. Use class property 'atomic' to
     * disable cmd wide transactions
     *
     * @internal
     *
     * @param string $cmd
     *
     * @throws exception\ControllerException|\Exception|exception\NotAuthorizedException
     */
    final protected function executeCommand($cmd)
    {
        $this->command = $cmd;

        if ($this->isJSON && Request::getBoolParam('json_blacklist')) {
            self::sendInternalError();
        }

        try {

            if ($this->atomic) {
                DBConnection::getInstance()->beginTransaction();
            }

            // call command
            if (method_exists($this, $cmd) === false) {
                Logger::getInstance()->error('method '.get_class($this).'::'.$cmd.' does not exist.');
                throw new exception\ControllerException('Die Aktion konnte nicht ausgeführt werden.');
            }
            $this->$cmd();

            if ($this->atomic) {
                DBConnection::getInstance()->commit();
            }

            $this->sendNotifications();

        } catch (exception\InvalidParameterException $e) {
            $this->error('Die folgenden Angaben sind unvollständig:');
            foreach ($e->getParameters() as $param) {
                $this->error($param['description'], $param['name']);
            }

        } catch (exception\ControllerException $e) {
            $this->error($e->getMessage());

        } catch (exception\NotAuthorizedException $e) {
            if (DBConnection::getInstance()->inTransaction()) {
                DBConnection::getInstance()->rollBack();
            }

            throw $e;

        } catch (exception\UnexpectedIntegrityConstraintViolationException $e) {
            $this->error('Integritätsprüfung fehlgeschlagen.');

        } catch (\Exception $e) {
            Logger::getInstance()->exception($e);
            $this->error('Fehler beim ausführen der Aktion.: '.$e->getMessage());
        }

        // by default, guess it failed and roll back the queries
        if (DBConnection::getInstance()->inTransaction()) {
            DBConnection::getInstance()->rollBack();
        }

    } // function


    /**
     * Get the form object for the current action.
     *
     * @api
     *
     * @return form\Form
     */
    final protected function getForm()
    {
        $class = str_replace('\\controller\\', '\\form\\', str_replace('Controller', 'Form', get_called_class()));
        $action = Router::getAction();

        $form = new $class();
        $form->$action();

        return $form;
    } // function


    /**
     * Uses the cmdRedirect class property and redirects to it. If nothing is set we try to go back to the referrer,
     * but this default behaviour is error prone, as that value it set by the browser and can not be trusted. Should
     * be changed in future versions.
     *
     * @api
     *
     * @throws exception\ControllerException
     *
     * @param string $route
     * @param array $arguments (optional)
     */
    final protected function redirect($route, array $arguments = [])
    {
        if (empty($route)) {
            throw new exception\ControllerException('Attempt to redirect to a unknown url');
        }

        // close transaction, if present
        if ($this->atomic) {
            DBConnection::getInstance()->commit();
        }

        $this->sendNotifications();
        Router::redirect($route, $arguments);
    } // function


    /**
     * Mark a request as error and log the message, go on with the associated view. This function is recoverable, if
     * you want to stop the further execution you have to throw an exception instead, which also be logged.
     *
     * @api
     *
     * @param string $message
     * @param array $param   (optional)
     */
    final protected function error($message, $param = null)
    {
        if ($this->isJSON) {
            $this->sendJson(false, $message);
        }

        if ($param === null) {
            Session::addErrorMsg($message);
        } else {
            Session::addErrorMsg($message, $param);
        }
        Logger::getInstance()->info(
            'AbstractController->error(): '.$message . (!empty($param) ? 'param: '.var_export($param, true) : '')
        );
    } // function


    /**
     * Mark a request as success and redirect the user to the given route.
     *
     * @api
     *
     * @param string $message
     * @param array $reference
     * @param string $name (optional)
     * @param array $redirectParameters (optional)
     */
    final protected function success($message, array $reference, $name = null, array $redirectParameters = [])
    {
        $this->logEvent($reference, $message, true);
        if ($this->isJSON) {
            $this->sendJson(true, $message);
        }

        Session::addSuccessMsg($message);
        $this->redirect($name, $redirectParameters);
    } // function


    /**
     *
     * @internal
     *
     * @param array $reference reference ids
     * @param string $description (optional) additional information
     * @param bool $success (optional)
     */
    final protected function logEvent(array $reference, $description = null, $success = false)
    {
        LogEvent::add([
            'event' => Router::getModule().'.'.$this->command.'.'.($success?'Success':'Fail'),
            'user_id' => Session::getUserId(),
            'reference_parameters' => json_encode($reference),
            'description' => $description
        ]);
    } // function


    /**
     * Send result (was action executed as desired and the resulting message) as JSON and stop further execution.
     *
     * @internal
     *
     * @param bool $success action was executed
     * @param string $message
     */
    private function sendJson($success, $message)
    {
        // close transaction, if present
        if ($this->atomic) {
            DBConnection::getInstance()->commit();
        }

        Logger::getInstance()->stackData();

        // @codingStandardsIgnoreStart
        echo json_encode(['success' => $success, 'message' => $message]);
        // @codingStandardsIgnoreEnd
        exit;
    } // function


    /**
     * Send notifications to users (as defined by the target behind the notification name and context)
     *
     * @api
     *
     * @throws exception\ControllerException
     *
     * @param string $notification
     * @param array $context
     * @param string $message
     */
    final protected function notify($notification, array $context, $message)
    {
        $target = Notification::getTarget($notification);
        $notification_id = Notification::getIdByName($notification);
        $user_ids = [];
        switch ($target) {

            // Send notification to the specified user.
            case 'user':
                if (!isset($context['user_id'])) {
                    throw new exception\ControllerException(
                        'Notification wurde ohne benötigten user_id Parameter aufgerufen.'
                    );
                }

                $user_ids[] = $context['user_id'];
                break;


            // Send notification all users which have the specified permission assigned (and probably restricted to
            // the given context).
            case 'permission':
                if (!isset($context['permission'])) {
                    throw new exception\ControllerException(
                        'Notification wurde ohne benötigten permission Parameter aufgerufen.'
                    );
                }

                $holders = Permission::getByName($context['permission'], $context['group_id']);
                foreach ($holders as $holder) {
                    $user_ids[] = $holder['user_id'];
                }
                break;
            default:
                foreach (Application::getNotificationTargetHandlers() as $handler) {
                    if ($handler->getTarget() === $target) {
                        $user_ids += $handler->handleContext($context);
                    }
                }
        } // switch $target

        foreach ($user_ids as $user_id) {
            $this->notifications[] = [
                'id' => $notification_id,
                'user_id' => $user_id,
                'message' => $message
            ];
        }
    } // function


    /**
     * Queue notifications.
     *
     * @internal
     */
    private function sendNotifications()
    {
        // add notifications to the queue after everything went fine (not part of the transaction to not disturb
        // functionality)
        foreach ($this->notifications as $notification) {

            $protocols = NotificationProtocol::getByUserId($notification['id'], $notification['user_id']);
            foreach ($protocols as $protocol) {

                // only allowed and used protocols
                if (!$protocol['allowed'] || !$protocol['used']) {
                    continue;
                }

                NotificationMessage::add([
                    'notification_id' => $notification['id'],
                    'protocol_id' => $protocol['id'],
                    'content' => $notification['message'],
                    'user_id' => $notification['user_id'],
                    'from_user_id' => Session::getUserId(),
                    'disabled' => false,
                    'visible' => true
                ]);
            } // foreach protocol
        } // foreach notification
    } // function
}// class
