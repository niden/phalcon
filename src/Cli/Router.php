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

use Phalcon\Di\DiInterface;
use Phalcon\Di\AbstractInjectionAware;
use Phalcon\Cli\Router\Route;
use Phalcon\Cli\Router\Exception;
use Phalcon\Cli\Router\RouteInterface;

/**
 * Phalcon\Cli\Router is the standard framework router. Routing is the process
 * of taking a command-line arguments and decomposing it into parameters to
 * determine which module, task, and action of that task should receive the
 * request.
 *
 *```php
 * $router = new \Phalcon\Cli\Router();
 *
 * $router->handle(
 *     [
 *         "module" => "main",
 *         "task"   => "videos",
 *         "action" => "process",
 *     ]
 * );
 *
 * echo $router->getTaskName();
 *```
 */
class Router extends AbstractInjectionAware
{
    protected $action;

    protected $defaultAction = null;

    protected $defaultModule = null;

    /**
     * @var array
     */
    protected array $defaultParams = [];

    protected $defaultTask = null;

    protected $matchedRoute;

    protected $matches;

    /**
     * @var string
     */
    protected string $module = '';

    /**
     * @var array
     */
    protected array $params = [];

    /**
     * @var array
     */
    protected array $routes = [];

    /**
     * @var string
     */
    protected string $task = '';

    /**
     * @var bool
     */
    protected bool $wasMatched = false;

    /**
     * Router constructor.
     *
     * @param bool $defaultRoutes
     */
    public function __construct(bool $defaultRoutes = true)
    {
        if (true === $defaultRoutes) {
            // Two routes are added by default to match
            // /:task/:action and /:task/:action/:params

            $this->routes[] = new Route(
                "#^(?::delimiter)?([a-zA-Z0-9\\_\\-]+)[:delimiter]{0,1}$#",
                [
                    'task' => 1
                ]
            );

            $this->routes[] = new Route(
                "#^(?::delimiter)?([a-zA-Z0-9\\_\\-]+):delimiter([a-zA-Z0-9\\.\\_]+)(:delimiter.*)*$#",
                [
                    'task'   => 1,
                    'action' => 2,
                    'params' => 3,
                ]
            );
        }
    }

    /**
     * Adds a route to the router
     *
     *```php
     * $router->add("/about", "About::main");
     *```
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return RouteInterface
     */
    public function add(string $pattern, $paths = null): RouteInterface
    {
        $route = new Route($pattern, $paths);
        $this->routes[] = $route;

        return $route;
    }

    /**
     * Returns processed action name
     *
     * @return string
     */
    public function getActionName(): string
    {
        return $this->action;
    }

    /**
     * Returns the route that matches the handled URI
     *
     * @return RouteInterface
     */
    public function getMatchedRoute(): RouteInterface
    {
        return $this->matchedRoute;
    }

    /**
     * Returns the sub expressions in the regular expression matched
     *
     * @return array
     */
    public function getMatches(): array
    {
        return $this->matches;
    }

    /**
     * Returns processed module name
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->module;
    }

    /**
     * Returns processed extra params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Returns a route object by its id
     *
     * @param int $routeId
     *
     * @return RouteInterface|bool
     */
    public function getRouteById(int $routeId)
    {
        foreach ($this->routes as $route) {
            if ($routeId === $route->getRouteId()) {
                return $route;
            }
        }

        return false;
    }

    /**
     * Returns a route object by its name
     *
     * @param string $name
     *
     * @return RouteInterface|bool
     */
    public function getRouteByName(string $name)
    {
        foreach ($this->routes as $route) {
            if ($name === $route->getName()) {
                return $route;
            }
        }

        return false;
    }

    /**
     * Returns all the routes defined in the router
     *
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Returns processed task name
     *
     * @return string
     */
    public function getTaskName(): string
    {
        return $this->task;
    }

    /**
     * Handles routing information received from command-line arguments
     *
     * @param array arguments
     */
    public function handle(array $arguments = null)
    {
//        var moduleName, taskName, actionName, params, route, parts, pattern,
//            routeFound, matches, paths, beforeMatch, converters, converter,
//            part, position, matchPosition, strParams;
//
//        let routeFound = false,
//            parts = [],
//            params = [],
//            matches = null,
//            this->wasMatched = false,
//            this->matchedRoute = null;
//
//        if typeof arguments != "array" {
//            if unlikely (typeof arguments != "string" && arguments !== null) {
//                throw new Exception("Arguments must be an array or string");
//            }
//
//            for route in reverse this->routes {
//                /**
//                 * If the route has parentheses use preg_match
//                 */
//                let pattern = route->getCompiledPattern();
//
//                if memstr(pattern, "^") {
//                    let routeFound = preg_match(pattern, arguments, matches);
//                } else {
//                    let routeFound = pattern == arguments;
//                }
//
//                /**
//                 * Check for beforeMatch conditions
//                 */
//                if routeFound {
//                    let beforeMatch = route->getBeforeMatch();
//
//                    if beforeMatch !== null {
//                        /**
//                         * Check first if the callback is callable
//                         */
//                        if unlikely !is_callable(beforeMatch) {
//                            throw new Exception(
//                                "Before-Match callback is not callable in matched route"
//                            );
//                        }
//
//                        /**
//                         * Check first if the callback is callable
//                         */
//                        let routeFound = call_user_func_array(
//                            beforeMatch,
//                            [
//                                arguments,
//                                route,
//                                this
//                            ]
//                        );
//                    }
//                }
//
//                if routeFound {
//                    /**
//                     * Start from the default paths
//                     */
//                    let paths = route->getPaths(),
//                        parts = paths;
//
//                    /**
//                     * Check if the matches has variables
//                     */
//                    if typeof matches == "array" {
//                        /**
//                         * Get the route converters if any
//                         */
//                        let converters = route->getConverters();
//
//                        for part, position in paths {
//                            if fetch matchPosition, matches[position] {
//                                /**
//                                 * Check if the part has a converter
//                                 */
//                                if fetch converter, converters[part] {
//                                    let parts[part] = call_user_func_array(
//                                        converter,
//                                        [matchPosition]
//                                    );
//                                } else {
//                                    /**
//                                     * Update the parts if there is no converter
//                                     */
//                                    let parts[part] = matchPosition;
//                                }
//                            } else {
//                                /**
//                                 * Apply the converters anyway
//                                 */
//                                if fetch converter, converters[part] {
//                                    let parts[part] = call_user_func_array(
//                                        converter,
//                                        [position]
//                                    );
//                                }
//                            }
//                        }
//
//                        /**
//                         * Update the matches generated by preg_match
//                         */
//                        let this->matches = matches;
//                    }
//
//                    let this->matchedRoute = route;
//
//                    break;
//                }
//            }
//
//            /**
//             * Update the wasMatched property indicating if the route was
//             * matched
//             */
//            if routeFound {
//                let this->wasMatched = true;
//            } else {
//                let this->wasMatched = false;
//
//                /**
//                 * The route wasn't found, try to use the not-found paths
//                 */
//                let this->module = this->defaultModule,
//                    this->task = this->defaultTask,
//                    this->action = this->defaultAction,
//                    this->params = this->defaultParams;
//
//                return this;
//            }
//        } else {
//            let parts = arguments;
//        }
//
//        let moduleName = null,
//            taskName = null,
//            actionName = null;
//
//        /**
//         * Check for a module
//         */
//        if fetch moduleName, parts["module"] {
//            unset parts["module"];
//        } else {
//            let moduleName = this->defaultModule;
//        }
//
//        /**
//         * Check for a task
//         */
//        if fetch taskName, parts["task"] {
//            unset parts["task"];
//        } else {
//            let taskName = this->defaultTask;
//        }
//
//        /**
//         * Check for an action
//         */
//        if fetch actionName, parts["action"] {
//            unset parts["action"];
//        } else {
//            let actionName = this->defaultAction;
//        }
//
//        /**
//         * Check for an parameters
//         */
//        if fetch params, parts["params"] {
//            if typeof params != "array" {
//                let strParams = substr(
//                    (string) params,
//                    1
//                );
//
//                if strParams {
//                    let params = explode(Route::getDelimiter(), strParams);
//                } else {
//                    let params = [];
//                }
//            }
//
//            unset parts["params"];
//        }
//
//        if count(params) {
//            let params = array_merge(params, parts);
//        } else {
//            let params = parts;
//        }
//
//        $this->module = $moduleName;
//        $this->task = $taskName;
//        $this->action = $actionName;
//        $this->params = $params;
    }

    /**
     * Sets the default action name
     *
     * @param string $actionName
     *
     * @return Router
     */
    public function setDefaultAction(string $actionName): Router
    {
        $this->defaultAction = $actionName;

        return $this;
    }

    /**
     * Sets the name of the default module
     *
     * @param string $moduleName
     *
     * @return Router
     */
    public function setDefaultModule(string $moduleName): Router
    {
        $this->defaultModule = $moduleName;

        return $this;
    }

    /**
     * Sets an array of default paths. If a route is missing a path the router
     * will use the defined here. This method must not be used to set a 404
     * route
     *
     *```php
     * $router->setDefaults(
     *     [
     *         "module" => "common",
     *         "action" => "index",
     *     ]
     * );
     *```
     *
     * @param array $defaults
     *
     * @return Router
     */
    public function setDefaults(array $defaults): Router
    {
        // Set a default module
        $this->defaultModule = $defaults['module'] ?? null;
        $this->defaultTask   = $defaults['task'] ?? null;
        $this->defaultAction = $defaults['action'] ?? null;
        $this->defaultParams = $defaults['params'] ?? [];

        return $this;
    }

    /**
     * Sets the default controller name
     *
     * @param string $taskName
     */
    public function setDefaultTask(string $taskName): void
    {
        $this->defaultTask = $taskName;
    }

    /**
     * Checks if the router matches any of the defined routes
     *
     * @return bool
     */
    public function wasMatched(): bool
    {
        return $this->wasMatched;
    }
}
