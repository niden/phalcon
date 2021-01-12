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
use Phalcon\Cli\Console\Exception;
use Phalcon\Cli\Router\Route;
use Phalcon\Support\Traits\PhpFileTrait;

use function array_merge;
use function array_shift;
use function class_exists;
use function implode;
use function is_array;
use function is_string;
use function strncmp;
use function substr;
use function trim;

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
    use PhpFileTrait;

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
     *
     * @param array $arguments
     *
     * @return false
     * @throws Exception
     */
    public function handle(array $arguments = [])
    {
        if (null === $this->container) {
            throw new Exception(
                'A dependency injection container has not been defined'
            );
        }

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
            if (true === isset($module['path'])) {
                $path = $module['path'];
                if (true !== $this->phpFileExists($path)) {
                    throw new Exception(
                        'Module definition path "' . $path . '" does not exist'
                    );
                }

                if (true === class_exists($className, false)) {
                    require $path;
                }
            }

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
        $args       = [];
        $opts       = [];
        $handleArgs = [];

        if (true === $shift && true !== empty($arguments)) {
            array_shift($arguments);
        }

        foreach ($arguments as $arg) {
            if (true === is_string($arg)) {
                if (0 === strncmp($arg, '--', 2)) {
                    $pos = mb_strpos($arg, '=');

                    if ($pos) {
                        $opts[trim(substr($arg, 2, $pos - 2))] = trim(substr($arg, $pos + 1));
                    } else {
                        $opts[trim(substr($arg, 2))] = true;
                    }
                } else {
                    if (0 === strncmp($arg, '-', 1)) {
                        $opts[substr($arg, 1)] = true;
                    } else {
                        $args[] = $arg;
                    }
                }
            } else {
                $args[] = $arg;
            }
        }

        if (true === $str) {
            $this->arguments = implode(Route::getDelimiter(), $args);
        } else {
            if (true !== empty($args)) {
                $handleArgs['task'] = array_shift($args);
            }

            if (true !== empty($args)) {
                $handleArgs['action'] = array_shift($args);
            }

            if (true !== empty($args)) {
                $handleArgs = array_merge($handleArgs, $args);
            }

            $this->arguments = $handleArgs;
        }

        $this->options = $opts;

        return $this;
    }
}
