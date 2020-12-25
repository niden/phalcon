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

use Phalcon\Application\AbstractApplication;
use Phalcon\Cli\Router\Route;
use Phalcon\Cli\Console\Exception;

/**
 * Class Console
 *
 * @package Phalcon\Cli
 *
 * @property array $arguments
 * @property array $options
 */
class Console extends AbstractApplication
{
    /**
     * @var array
     */
    protected array $arguments = [];

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * Handle the whole command-line tasks
     */
    public function handle(array $arguments = null)
    {
//        let container = this->container;
//
//        if unlikely typeof container != "object" {
//            throw new Exception(
//                Exception::containerServiceNotFound("internal services")
//            );
//        }
//
//        let eventsManager = <ManagerInterface> this->eventsManager;
//
        /**
         * Call boot event, this allows the developer to perform initialization
         * actions
         */
        if (false === $this->fireEvent('console:boot')) {
            return false;
        }

        $router = $this->container->getShared('router');
        if (true === empty($arguments) && true !== empty($this->arguments)) {
            $router->handle($this->arguments);
        } else {
            $router->handle($arguments);
        }
//
        /**
         * If the router doesn't return a valid module we use the default module
         */
        $moduleName = $router->getModuleName();

        if (true !== empty($moduleName)) {
            $moduleName = $this->defaultModule;
        }

        if (true !== empty($moduleName)) {
            if (false === $this->fireEvent('console:beforeStartModule', $moduleName)) {
                return false;
            }

            if (true !== isset($this->modules[$moduleName])) {
                throw new Exception(
                    'Module "' . $moduleName . '" is not registered in the console container'
                );
            }

            $module = $this->modules[$moduleName];

            if (true !== is_array($module)) {
                throw new Exception('Invalid module definition path');
            }

            $className = $module['className'] ?? 'Module';

//            if fetch path, module["path"] {
//                if unlikely !file_exists(path) {
//                    throw new Exception(
//                        "Module definition path '" . path . "' doesn't exist"
//                    );
//                }
//
//                if !class_exists(className, false) {
//                    require $path;
//                }
//            }

            $moduleObject = $this->container->get($className);
            $moduleObject->registerAutoloaders($this->container);
            $moduleObject->registerServices($this->container);

            if (false === $this->fireEvent('console:afterStartModule', $moduleObject)) {
                return false;
            }
        }

        $dispatcher = $this->container->getShared('dispatcher');

        $dispatcher
            ->setModuleName($router->getModuleName())
            ->setTaskName($router->getTaskName())
            ->setActionName($router->getActionName())
            ->setParams($router->getParams())
            ->setOptions($this->options)
        ;

        if (false === $this->fireEvent('console:beforeHandleTask', $dispatcher)) {
            return false;
        }

        $task = $dispatcher->dispatch();

        $this->fireEvent('console:afterHandleTask');

        return $task;
    }

    /**
     * Set an specific argument
     *
     * @param array $arguments
     * @param bool  $str
     * @param bool  $shift
     *
     * @return Console
     */
    public function setArgument(
        array $arguments = [],
        bool $str = true,
        bool $shift = true
    ): Console {
//        var arg, pos, args, opts, handleArgs;
//
//        let args = [],
//            opts = [],
//            handleArgs = [];
//
//        if shift && count(arguments) {
//            array_shift(arguments);
//        }
//
//        for arg in arguments {
//            if typeof arg == "string" {
//                if strncmp(arg, "--", 2) == 0 {
//                    let pos = strpos(arg, "=");
//
//                    if pos {
//                        let opts[trim(substr(arg, 2, pos - 2))] = trim(substr(arg, pos + 1));
//                    } else {
//                        let opts[trim(substr(arg, 2))] = true;
//                    }
//                } else {
//                    if strncmp(arg, "-", 1) == 0 {
//                        let opts[substr(arg, 1)] = true;
//                    } else {
//                        let args[] = arg;
//                    }
//                }
//            } else {
//                let args[] = arg;
//            }
//        }
//
//        if str {
//            let this->arguments = implode(
//                Route::getDelimiter(),
//                args
//            );
//        } else {
//            if count(args) {
//                let handleArgs["task"] = array_shift(args);
//            }
//
//            if count(args) {
//                let handleArgs["action"] = array_shift(args);
//            }
//
//            if count(args) {
//                let handleArgs = array_merge(handleArgs, args);
//            }
//
//            let this->arguments = handleArgs;
//        }
//
//        let this->options = opts;
//
        return $this;
    }
}
