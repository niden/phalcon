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

use Phalcon\Cli\Router\RouteInterface;

/**
 * Interface RouterInterface
 *
 * @package Phalcon\Cli
 */
interface RouterInterface
{
    /**
     * Adds a route to the router on any HTTP method
     *
     * @param string     $pattern
     * @param mixed|null $paths
     *
     * @return RouteInterface
     */
    public function add(string $pattern, $paths = null): RouteInterface;

    /**
     * Returns processed action name
     *
     * @return string
     */
    public function getActionName(): string;

    /**
     * Returns the route that matches the handled URI
     *
     * @return RouteInterface
     */
    public function getMatchedRoute(): RouteInterface;

    /**
     * Return the sub expressions in the regular expression matched
     *
     * @return array
     */
    public function getMatches(): array;

    /**
     * Returns processed module name
     *
     * @return string
     */
    public function getModuleName(): string;

    /**
     * Returns processed extra params
     *
     * @return array
     */
    public function getParams(): array;

    /**
     * Returns a route object by its id
     *
     * @param mixed $id
     *
     * @return RouteInterface
     */
    public function getRouteById($id): RouteInterface;

    /**
     * Returns a route object by its name
     *
     * @param string $name
     *
     * @return RouteInterface
     */
    public function getRouteByName(string $name): RouteInterface;

    /**
     * Return all the routes defined in the router
     *
     * @return array
     */
    public function getRoutes(): array;

    /**
     * Returns processed task name
     *
     * @return string
     */
    public function getTaskName(): string;

    /**
     * Handles routing information received from the rewrite engine
     *
     * @param array arguments
     */
    public function handle(array $arguments = null);

    /**
     * Sets the default action name
     *
     * @param string $actionName
     */
    public function setDefaultAction(string $actionName): void;

    /**
     * Sets the name of the default module
     *
     * @param string $moduleName
     */
    public function setDefaultModule(string $moduleName): void;

    /**
     * Sets an array of default paths
     *
     * @param array $defaults
     */
    public function setDefaults(array $defaults): void;

    /**
     * Sets the default task name
     *
     * @param string $taskName
     */
    public function setDefaultTask(string $taskName): void;

    /**
     * Check if the router matches any of the defined routes
     *
     * @return bool
     */
    public function wasMatched(): bool;
}