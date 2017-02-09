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
        if(!class_exists($ctrlName)) {
            throw new \ErrorException('class '.$ctrlName.' not found');
        }
        $ctrl = new $ctrlName(Request::getBoolParam('resultAsJSON'));
        $ctrl->executeCommand($action);
    } // function


    /**
     * Builds transactions about command executions and catches \Exceptions, set error messages and redirects to
     * bypass form re-submit of data. Specific actions are defined in handleCommand. Use class property 'atomic' to
     * disable cmd wide transactions
     *
     * @todo make final again, after merging logic of now external abstract controllers (e.g. AbstractApiController)
     *
     * @internal
     *
     * @param string $cmd
     *
     * @throws exception\ControllerException|\Exception|exception\NotAuthorizedException
     */
    protected function executeCommand($cmd)
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
                Logger::getInstance()->error(_s('method %s::%s does not exist.', get_class($this), $cmd));
                throw new exception\ControllerException(_s('Die Aktion konnte nicht ausgeführt werden.'));
            }
            $this->$cmd();

            if ($this->atomic && DBConnection::getInstance()->inTransaction()) {
                DBConnection::getInstance()->commit();
            }

        } catch (exception\InvalidParameterException $e) {
            $this->error(_s('Die folgenden Angaben sind unvollständig:'));
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
            $this->error(_s('Integritätsprüfung fehlgeschlagen.'));

        } catch (\Exception $e) {
            Logger::getInstance()->exception($e);
            $this->error(_s('Fehler beim ausführen der Aktion.: ').$e->getMessage());
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
     * @param bool $terminate_processing if set to false, Location header is set, but processing will continue
     *                                   (required for API processing where views need to be rendered also on action
     *                                    requests). default is true
     */
    final protected function redirect($route, array $arguments = [], $terminate_processing = true)
    {
        if (empty($route)) {
            throw new exception\ControllerException(_s('Attempt to redirect to a unknown url'));
        }

        // close transaction, if present
        if ($this->atomic && DBConnection::getInstance()->inTransaction()) {
            DBConnection::getInstance()->commit();
        }

        if($terminate_processing) {
            Router::redirect($route, $arguments);
        } else {
            // don't end request processing, just set the Location header
            header('Location: '. Router::reverse($route, $arguments, true));
        }
    } // function


    /**
     * Mark a request as error and log the message, go on with the associated view. This function is recoverable, if
     * you want to stop the further execution you have to throw an exception instead, which also be logged.
     *
     * @todo make final again, after merging logic of now external abstract controllers (e.g. AbstractApiController)
     *
     * @api
     *
     * @param string $message
     * @param array $param   (optional)
     */
    protected function error($message, $param = null)
    {
        if ($param === null) {
            Session::addErrorMsg($message);
        } else {
            Session::addErrorMsg($message, $param);
        }
        Logger::getInstance()->info(
            'AbstractController->error(): '.$message . (!empty($param) ? 'param: '.var_export($param, true) : '')
        );

        if ($this->isJSON) {
            if(http_response_code() < self::HTTP_CODE_BAD_REQUEST) {
                http_response_code(self::HTTP_CODE_UNPROCESSABLE);
            }
            $this->sendJson($message);
        }
    } // function


    /**
     * Mark a request as success and redirect the user to the given route.
     *
     * @todo make final again, after merging logic of now external abstract controllers (e.g. AbstractApiController)
     *
     * @api
     *
     * @param string $message
     * @param array $reference
     * @param string $name (optional)
     * @param array $redirectParameters (optional)
     */
    protected function success($message, array $reference, $name = null, array $redirectParameters = [])
    {
        $this->logEvent($reference, $message, true);

        Session::addSuccessMsg($message);

        if(empty($name)) {
            return;
        }

        if ($this->isJSON) {
            $this->redirect($name, $redirectParameters, false);
            $this->sendJson();
        } else {
            $this->redirect($name, $redirectParameters);
        }
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
    private function sendJson($message = null)
    {
        // close transaction, if present
        if ($this->atomic && DBConnection::getInstance()->inTransaction()) {
            DBConnection::getInstance()->commit();
        }

        header('Content-type: application/json');

        if($message !== null) {
            // @codingStandardsIgnoreStart
            echo StringUtil::jsonEncode(['error' => $message]);
            // @codingStandardsIgnoreEnd
        }
        //exit;
    } // function

}// class
