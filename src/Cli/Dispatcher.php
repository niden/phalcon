<?php

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Cli;

use Exception as BaseException;
use Phalcon\Cli\Dispatcher\Exception;
use Phalcon\Dispatcher\AbstractDispatcher;

use function array_merge;
use function array_values;
use function call_user_func_array;

/**
 * Dispatching is the process of taking the command-line arguments, extracting
 * the module name, task name, action name, and optional parameters contained in
 * it, and then instantiating a task and calling an action on it.
 *
 * ```php
 * use Phalcon\Di;
 * use Phalcon\Cli\Dispatcher;
 *
 * $di = new Di();
 *
 * $dispatcher = new Dispatcher();
 *
 * $dispatcher->setDi($di);
 *
 * $dispatcher->setTaskName("posts");
 * $dispatcher->setActionName("index");
 * $dispatcher->setParams([]);
 *
 * $handle = $dispatcher->dispatch();
 * ```
 *
 * @property string $defaultHandler
 * @property string $defaultAction
 * @property string $handlerSuffix
 * @property array  $options
 */
class Dispatcher extends AbstractDispatcher implements DispatcherInterface
{
    /**
     * @var string
     */
    protected string $defaultHandler = 'main';

    /**
     * @var string
     */
    protected string $defaultAction = 'main';

    /**
     * @var string
     */
    protected string $handlerSuffix = 'Task';

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * Calls the action method.
     */
    public function callActionMethod(
        $handler, 
        string $actionMethod, 
        array $params = []
    ) {
        // This is to make sure that the parameters are zero-indexed and
        // their order isn't overridden by any options when we merge the array.
        $parameters = array_values($params);
        $parameters = array_merge($parameters, $this->options);

        return call_user_func_array([$handler, $actionMethod], $parameters);
    }

    /**
     * Returns the active task in the dispatcher
     *
     * @return TaskInterface
     */
    public function getActiveTask(): TaskInterface
    {
        return $this->activeHandler;
    }

    /**
     * Returns the latest dispatched controller
     */
    public function getLastTask(): TaskInterface
    {
        return $this->lastHandler;
    }

    /**
     * Gets an option by its name or numeric index
     *
     * @param  mixed $option
     * @param  string|array $filters
     * @param  mixed $defaultValue
     */
    public function getOption($option, $filters = null, $defaultValue = null)
    {
        if (true !== isset($this->options[$option])) {
            return $defaultValue;
        }

        if (null === $filters) {
            return $this->options[$option];
        }

        if (null === $this->container) {
            $this->throwDispatchException(
                'filter service',
                Exception::EXCEPTION_NO_DI
            );
        }

        $filter = $this->container->getShared('filter');

        return $filter->sanitize($this->options[$option], $filters);
    }

    /**
     * Get dispatched options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Gets last dispatched task name
     *
     * @return string
     */
    public function getTaskName(): string
    {
        return $this->handlerName;
    }

    /**
     * Gets the default task suffix
     *
     * @return string
     */
    public function getTaskSuffix(): string
    {
        return $this->handlerSuffix;
    }

    /**
     * Check if an option exists
     *
     * @param mixed $option
     *
     * @return bool
     */
    public function hasOption($option): bool
    {
        return isset($this->options[$option]);
    }

    /**
     * Sets the default task name
     *
     * @param string $taskName
     */
    public function setDefaultTask(string $taskName): DispatcherInterface
    {
        $this->defaultHandler = $taskName;

        return $this;
    }

    /**
     * Set the options to be dispatched
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options): DispatcherInterface
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Sets the task name to be dispatched
     *
     * @param string $taskName
     *
     * @return DispatcherInterface
     */
    public function setTaskName(string $taskName): DispatcherInterface
    {
        $this->handlerName = $taskName;

        return $this;
    }

    /**
     * Sets the default task suffix
     *
     * @param string $taskSuffix
     *
     * @return DispatcherInterface
     */
    public function setTaskSuffix(string $taskSuffix): DispatcherInterface
    {
        $this->handlerSuffix = $taskSuffix;

        return $this;
    }

    /**
     * Handles a user exception
     *
     * @param \Exception $exception
     *
     * @return false
     */
    protected function handleException(BaseException $exception)
    {
        if (false === $this->fireEvent('dispatch:beforeException', $exception)) {
            return false;
        }
    }

    /**
     * Throws an internal exception
     *
     * @param string $message
     * @param int    $exceptionCode
     *
     * @return false
     */
    protected function throwDispatchException(string $message, int $exceptionCode = 0)
    {
        $exception = new Exception($message, $exceptionCode);

        if (false === $this->handleException($exception)) {
            return false;
        }

        throw $exception;
    }
}
