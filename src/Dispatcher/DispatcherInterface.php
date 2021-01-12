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

namespace Phalcon\Dispatcher;

/**
 * Interface for Phalcon\Dispatcher\AbstractDispatcher
 */
interface DispatcherInterface
{
    /**
     * Dispatches a handle action taking into account the routing parameters
     *
     * @return object|false
     */
    public function dispatch();

    /**
     * Forwards the execution flow to another controller/action
     *
     * @param array $forward
     */
    public function forward(array $forward): void;

    /**
     * Gets last dispatched action name
     *
     * @return string
     */
    public function getActionName(): string;

    /**
     * Gets the default action suffix
     *
     * @return string
     */
    public function getActionSuffix(): string;

    /**
     * Gets the default handler suffix
     *
     * @return string
     */
    public function getHandlerSuffix(): string;

    /**
     * Gets a param by its name or numeric index
     *
     * @param mixed        $param
     * @param string|array $filters
     */
    public function getParam($param, $filters = null);

    /**
     * Gets action params
     *
     * @return array
     */
    public function getParams(): array;

    /**
     * Returns value returned by the latest dispatched action
     *
     * @return mixed
     */
    public function getReturnedValue();

    /**
     * Check if a param exists
     *
     * @param mixed $param
     *
     * @return bool
     */
    public function hasParam($param): bool;

    /**
     * Checks if the dispatch loop is finished or has more pendent
     * controllers/tasks to dispatch
     *
     * @return bool
     */
    public function isFinished(): bool;

    /**
     * Sets the action name to be dispatched
     *
     * @param string $actionName
     */
    public function setActionName(string $actionName): void;

    /**
     * Sets the default action suffix
     *
     * @param string $actionSuffix
     */
    public function setActionSuffix(string $actionSuffix): void;

    /**
     * Sets the default action name
     *
     * @param string $actionName
     */
    public function setDefaultAction(string $actionName): void;

    /**
     * Sets the default namespace
     *
     * @param string $defaultNamespace
     */
    public function setDefaultNamespace(string $defaultNamespace): void;

    /**
     * Sets the default suffix for the handler
     *
     * @param string $handlerSuffix
     */
    public function setHandlerSuffix(string $handlerSuffix): void;

    /**
     * Sets the module name which the application belongs to
     *
     * @param string $moduleName
     */
    public function setModuleName(string $moduleName): void;

    /**
     * Sets the namespace which the controller belongs to
     *
     * @param string $namespaceName
     */
    public function setNamespaceName(string $namespaceName): void;

    /**
     * Set a param by its name or numeric index
     *
     * @param mixed $param
     * @param mixed $value
     */
    public function setParam($param, $value): void;

    /**
     * Sets action params to be dispatched
     *
     * @param array $params
     */
    public function setParams(array $params): void;
}