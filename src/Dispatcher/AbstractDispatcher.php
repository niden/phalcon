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

use Exception as BaseException;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Di\Traits\InjectionAwareTrait;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\Traits\EventsAwareTrait;
use Phalcon\Mvc\Model\BinderInterface;
use Phalcon\Support\Str\Traits\CamelizeTrait;
use Phalcon\Support\Str\Traits\EndsWithTrait;
use Psr\SimpleCache\CacheInterface;
use function call_user_func_array;
use function class_exists;
use function is_array;
use function is_callable;
use function is_object;
use function lcfirst;
use function mb_strpos;
use function method_exists;
use function spl_object_hash;

/**
 * This is the base class for Phalcon\Mvc\Dispatcher and Phalcon\Cli\Dispatcher.
 * This class can't be instantiated directly, you can use it to create your own
 * dispatchers.
 *
 *
 * @property string|null          $actionName
 * @property string               $actionSuffix
 * @property mixed|null           $activeHandler
 * @property array                $activeMethodMap
 * @property array                $camelCaseMap
 * @property string               $defaultAction
 * @property string|null          $defaultNamespace
 * @property string               $defaultHandler
 * @property array                $handlerHashes
 * @property string|null          $handlerName
 * @property string               $handlerSuffix
 * @property bool                 $finished
 * @property bool                 $forwarded
 * @property bool                 $isControllerInitialize
 * @property mixed|null           $lastHandler
 * @property BinderInterface|null $modelBinder
 * @property bool                 $modelBinding
 * @property string|null          $moduleName
 * @property string|null          $namespaceName
 * @property array                $params
 * @property string|null          $previousActionName
 * @property string|null          $previousHandlerName
 * @property string|null          $previousNamespaceName
 * @property mixed|null           $returnedValue
 */
abstract class AbstractDispatcher
    implements DispatcherInterface, EventsAwareInterface, InjectionAwareInterface
{
    use CamelizeTrait;
    use EndsWithTrait;
    use EventsAwareTrait;
    use InjectionAwareTrait;

    /**
     * @var string|null
     */
    protected ?string $actionName = null;

    /**
     * @var string
     */
    protected string $actionSuffix = 'Action';

    /**
     * @var mixed|null
     */
    protected $activeHandler = null;

    /**
     * @var array
     */
    protected array $activeMethodMap = [];

    /**
     * @var array
     */
    protected array $camelCaseMap = [];

    /**
     * @var string
     */
    protected string $defaultAction = '';

    /**
     * @var string|null
     */
    protected ?string $defaultNamespace = null;

    /**
     * @var string
     */
    protected string $defaultHandler = '';

    /**
     * @var array
     */
    protected array $handlerHashes = [];

    /**
     * @var string|null
     */
    protected ?string $handlerName = null;

    /**
     * @var string
     */
    protected string $handlerSuffix = '';

    /**
     * @var bool
     */
    protected bool $finished = false;

    /**
     * @var bool
     */
    protected bool $forwarded = false;

    /**
     * @var bool
     */
    protected bool $isControllerInitialize = false;

    /**
     * @var mixed|null
     */
    protected $lastHandler = null;

    /**
     * @var mixed|null
     */
    protected $modelBinder = null;

    /**
     * @var bool
     */
    protected bool $modelBinding = false;

    /**
     * @var string|null
     */
    protected ?string $moduleName = null;

    /**
     * @var string|null
     */
    protected ?string $namespaceName = null;

    /**
     * @var array
     */
    protected array $params = [];

    /**
     * @var string|null
     */
    protected ?string $previousActionName = null;

    /**
     * @var string|null
     */
    protected ?string $previousHandlerName = null;

    /**
     * @var string|null
     */
    protected ?string $previousNamespaceName = null;

    /**
     * @var mixed|null
     */
    protected $returnedValue = null;

    /**
     * @param mixed  $handler
     * @param string $actionMethod
     * @param array  $params
     *
     * @return false|mixed
     */
    public function callActionMethod(
        $handler,
        string $actionMethod,
        array $params = []
    ) {
        return call_user_func_array([$handler, $actionMethod], $params);
    }

    /**
     * Process the results of the router by calling into the appropriate
     * controller action(s) including any routing data or injected parameters.
     *
     * @return object|false Returns the dispatched handler class (the
     *                      Controller for Mvc dispatching or a Task for CLI
     *                      dispatching) or <tt>false</tt> if an exception
     *                      occurred and the operation was stopped by returning
     *                      <tt>false</tt> in the exception handler.
     *
     * @throws \Exception if any uncaught or unhandled exception occurs during
     *                    the dispatcher process.
     */
    public function dispatch()
    {
        if (null === $this->container) {
            $this->throwDispatchException(
                'container',
                Exception::EXCEPTION_NO_DI
            );

            return false;
        }
        $this->finished = true;
        if (null !== $this->eventsManager) {
            try {
                // Calling beforeDispatchLoop event
                // Note: Allow user to forward in the beforeDispatchLoop.
                if (false === $this->fireEvent('dispatch:beforeDispatchLoop') &&
                    false !== $this->finished
                ) {
                    return false;
                }
            } catch (BaseException $ex) {
                // Exception occurred in beforeDispatchLoop.

                /**
                 * The user can optionally forward now in the
                 * `dispatch:beforeException` event or return <tt>false</tt> to
                 * handle the exception and prevent it from bubbling. In the
                 * event the user does forward but does or does not return
                 * false, we assume the forward takes precedence. The returning
                 * false intuitively makes more sense when inside the dispatch
                 * loop and technically we are not here. Therefore, returning
                 * false only impacts whether non-forwarded exceptions are
                 * silently handled or bubbled up the stack. Note that this
                 * behavior is slightly different than other subsequent events
                 * handled inside the dispatch loop.
                 */

                $status = $this->handleException($ex);

                if (false !== $this->finished) {
                    // No forwarding
                    if (false === $status) {
                        return false;
                    }

                    // Otherwise, bubble Exception
                    throw $ex;
                }

                // Otherwise, user forwarded, continue
            }
        }

        $value            = null;
        $handler          = null;
        $numberDispatches = 0;
        $this->finished   = false;

        while (true !== $this->finished) {
            $numberDispatches++;

            // Throw an exception after 256 consecutive forwards
            if (256 === $numberDispatches) {
                $this->throwDispatchException(
                    'Dispatcher has detected a cyclic routing causing stability problems',
                    Exception::EXCEPTION_CYCLIC_ROUTING
                );

                break;
            }

            $this->finished = true;
            $this->resolveEmptyProperties();

            if (null !== $this->eventsManager) {
                try {
                    // Calling "dispatch:beforeDispatch" event
                    if (
                        false === $this->fireEvent('dispatch:beforeDispatch') ||
                        false === $this->finished
                    ) {
                        continue;
                    }
                } catch (Exception $ex) {
                    if (
                        false === $this->handleException($ex) ||
                        false === $this->finished
                    ) {
                        continue;
                    }

                    throw $ex;
                }
            }

            $handlerClass = $this->getHandlerClass();

            /**
             * Handlers are retrieved as shared instances from the Service
             * Container
             */
            $hasService = (bool) $this->container->has($handlerClass);

            if (true !== $hasService) {
                /**
                 * DI doesn't have a service with that name, try to load it
                 * using an autoloader
                 */
                $hasService = (bool) class_exists($handlerClass);
            }

            // If the service can be loaded we throw an exception
            if (true !== $hasService) {
                $status = $this->throwDispatchException(
                    $handlerClass . ' handler class cannot be loaded',
                    Exception::EXCEPTION_HANDLER_NOT_FOUND
                );

                if (false === $status && false === $this->finished) {
                    continue;
                }

                break;
            }

            $handler = $this->container->getShared($handlerClass);

            // Handlers must be only objects
            if (true !== is_object($handler)) {
                $status = $this->throwDispatchException(
                    'Invalid handler returned from the services container',
                    Exception::EXCEPTION_INVALID_HANDLER
                );

                if (false === $status && false === $this->finished) {
                    continue;
                }

                break;
            }

            // Check if the handler is new (hasn't been initialized).
            $handlerHash  = spl_object_hash($handler);
            $isNewHandler = false === isset($this->handlerHashes[$handlerHash]);

            if (true === $isNewHandler) {
                $this->handlerHashes[$handlerHash] = true;
            }

            $this->activeHandler = $handler;
            $namespaceName       = $this->namespaceName;
            $handlerName         = $this->handlerName;
            $actionName          = $this->actionName;
            $params              = $this->params;

            /**
             * Check if the params is an array
             */
            if (true !== is_array($params)) {
                /**
                 * An invalid parameter variable was passed throw an exception
                 */
                $status = $this->throwDispatchException(
                    "Action parameters must be an Array",
                    Exception::EXCEPTION_INVALID_PARAMS
                );

                if (false === $status && false === $this->finished) {
                    continue;
                }

                break;
            }

            // Check if the method exists in the handler
            $actionMethod = $this->getActiveMethod();

            if (true !== is_callable([$handler, $actionMethod])) {
                if (null !== $this->eventsManager) {
                    if (false === $this->fireEvent('dispatch:beforeNotFoundAction')) {
                        continue;
                    }

                    if (false === $this->finished) {
                        continue;
                    }
                }

                /**
                 * Try to throw an exception when an action isn't defined on the
                 * object
                 */
                $status = $this->throwDispatchException(
                    "Action '" . $actionName . "' was not found on handler '" . $handlerName . "'",
                    Exception::EXCEPTION_ACTION_NOT_FOUND
                );

                if (false === $status && false === $this->finished) {
                    continue;
                }

                break;
            }

            /**
             * In order to ensure that the `initialize()` gets called we'll
             * destroy the current handlerClass from the DI container in the
             * event that an error occurs and we continue out of this block.
             * This is necessary because there is a disjoin between retrieval of
             * the instance and the execution of the `initialize()` event. From
             * a coding perspective, it would have made more sense to probably
             * put the `initialize()` prior to the beforeExecuteRoute which
             * would have solved this. However, for posterity, and to remain
             * consistency, we'll ensure the default and documented behavior
             * works correctly.
             */
            if (null !== $this->eventsManager) {
                try {
                    // Calling "dispatch:beforeExecuteRoute" event
                    if (
                        false === $this->fireEvent('dispatch:beforeExecuteRoute') ||
                        false === $this->finished
                    ) {
                        $this->container->remove($handlerClass);
                        continue;
                    }
                } catch (Exception $ex) {
                    if (
                        false === $this->handleException($ex) ||
                        false === $this->finished
                    ) {
                        $this->container->remove($handlerClass);
                        continue;
                    }

                    throw $ex;
                }
            }

            if (true === method_exists($handler, 'beforeExecuteRoute')) {
                try {
                    // Calling "beforeExecuteRoute" as direct method
                    if (
                        false === $handler->beforeExecuteRoute($this) ||
                        false === $this->finished
                    ) {
                        $this->container->remove($handlerClass);
                        continue;
                    }
                } catch (Exception $ex) {
                    if (
                        false === $this->handleException($ex) ||
                        false === $this->finished
                    ) {
                        $this->container->remove($handlerClass);
                        continue;
                    }

                    throw $ex;
                }
            }

            /**
             * Call the "initialize" method just once per request
             *
             * Note: The `dispatch:afterInitialize` event is called regardless
             *       of the presence of an `initialize()` method. The naming is
             *       poor; however, the intent is for a more global "constructor
             *       is ready to go" or similarly "__onConstruct()" methodology.
             *
             * Note: In Phalcon 4.0, the `initialize()` and
             * `dispatch:afterInitialize` event will be handled prior to the
             * `beforeExecuteRoute` event/method blocks. This was a bug in the
             * original design that was not able to change due to widespread
             * implementation. With proper documentation change and blog posts
             * for 4.0, this change will happen.
             *
             * @see https://github.com/phalcon/cphalcon/pull/13112
             */
            if (true === $isNewHandler) {
                if (true === method_exists($handler, 'initialize')) {
                    try {
                        $this->isControllerInitialize = true;
                        $handler->initialize();
                    } catch (Exception $ex) {
                        $this->isControllerInitialize = false;

                        /**
                         * If this is a dispatch exception (e.g. From
                         * forwarding) ensure we don't handle this twice. In
                         * order to ensure this doesn't happen all other
                         * exceptions thrown outside this method in this class
                         * should not call "throwDispatchException" but instead
                         * throw a normal Exception.
                         */
                        if (
                            false === $this->handleException($ex) ||
                            false === $this->finished
                        ) {
                            continue;
                        }

                        throw $ex;
                    }
                }

                $this->isControllerInitialize = false;

                /**
                 * Calling "dispatch:afterInitialize" event
                 */
                if (null !== $this->eventsManager) {
                    try {
                        if (
                            false === $this->fireEvent('dispatch:afterInitialize') ||
                            false === $this->finished
                        ) {
                            continue;
                        }
                    } catch (Exception $ex) {
                        if (
                            false === $this->handleException($ex) ||
                            false === $this->finished
                        ) {
                            continue;
                        }

                        throw $ex;
                    }
                }
            }

            if (true === $this->modelBinding) {
                $bindCacheKey = '_PHMB_' . $handlerClass . '_' . $actionMethod;

                $params = $this->modelBinder->bindToHandler(
                    $handler,
                    $params,
                    $bindCacheKey,
                    $actionMethod
                );
            }

            /**
             * Calling afterBinding
             */
            if (null !== $this->eventsManager) {
                if (false === $this->fireEvent('dispatch:afterBinding')) {
                    continue;
                }

                /**
                 * Check if the user made a forward in the listener
                 */
                if (false === $this->finished) {
                    continue;
                }
            }

            /**
             * Calling afterBinding as callback and event
             */
            if (true === method_exists($handler, 'afterBinding')) {
                if (false === $handler->afterBinding($this)) {
                    continue;
                }

                /**
                 * Check if the user made a forward in the listener
                 */
                if (false === $this->finished) {
                    continue;
                }
            }

            /**
             * Save the current handler
             */
            $this->lastHandler = $handler;

            try {
                /**
                 * We update the latest value produced by the latest handler
                 */
                $this->returnedValue = $this->callActionMethod(
                    $handler,
                    $actionMethod,
                    $params
                );

                if (false === $this->finished) {
                    continue;
                }
            } catch (Exception $ex) {
                if (
                    false === $this->handleException($ex) ||
                    false === $this->finished
                ) {
                    continue;
                }

                throw $ex;
            }

            /**
             * Calling "dispatch:afterExecuteRoute" event
             */
            if (null !== $this->eventsManager) {
                try {
                    if (
                        false === $this->fireEvent('dispatch:afterExecuteRoute', $value) ||
                        false === $this->finished
                    ) {
                        continue;
                    }
                } catch (Exception $ex) {
                    if (
                        false === $this->handleException($ex) ||
                        false === $this->finished
                    ) {
                        continue;
                    }

                    throw $ex;
                }
            }

            /**
             * Calling "afterExecuteRoute" as direct method
             */
            if (true === method_exists($handler, 'afterExecuteRoute')) {
                try {
                    if (
                        false === $handler->afterExecuteRoute($this, $value) ||
                        false === $this->finished
                    ) {
                        continue;
                    }
                } catch (Exception $ex) {
                    if (
                        false === $this->handleException($ex) ||
                        false === $this->finished
                    ) {
                        continue;
                    }

                    throw $ex;
                }
            }

            // Calling "dispatch:afterDispatch" event
            if (null !== $this->eventsManager) {
                try {
                    $this->fireEvent('dispatch:afterDispatch', $value);
                } catch (Exception $ex) {
                    /**
                     * Still check for finished here as we want to prioritize
                     * `forwarding()` calls
                     */
                    if (
                        false === $this->handleException($ex) ||
                        false === $this->finished
                    ) {
                        continue;
                    }

                    throw $ex;
                }
            }
        }

        if (null !== $this->eventsManager) {
            try {
                // Calling "dispatch:afterDispatchLoop" event
                // Note: We don't worry about forwarding in after dispatch loop.
                $this->fireEvent('dispatch:afterDispatchLoop');
            } catch (Exception $ex) {
                // Exception occurred in afterDispatchLoop.
                if (false === $this->handleException($ex)) {
                    return false;
                }

                // Otherwise, bubble Exception
                throw $ex;
            }
        }

        return $handler;
    }

    /**
     * Forwards the execution flow to another controller/action.
     *
     * ```php
     * $this->dispatcher->forward(
     *     [
     *         "controller" => "posts",
     *         "action"     => "index",
     *     ]
     * );
     * ```
     *
     * @param array $forward
     */
    public function forward(array $forward): void
    {
        if (true === $this->isControllerInitialize) {
            /**
             * Note: Important that we do not throw a "throwDispatchException"
             * call here. This is important because it would allow the
             * application to break out of the defined logic inside the
             * dispatcher which handles all dispatch exceptions.
             */
            throw new Exception(
                "Forwarding inside a controller's initialize() method is forbidden"
            );
        }

        /**
         * Save current values as previous to ensure calls to getPrevious
         * methods don't return null.
         */
        $this->previousNamespaceName = $this->namespaceName;
        $this->previousHandlerName   = $this->handlerName;
        $this->previousActionName    = $this->actionName;

        // Check if we need to forward to another namespace
        $this->namespaceName = $forward['namespace'] ?? $this->namespaceName;

        // Check if we need to forward to another controller.
        $this->handlerName = $forward['controller'] ?? $this->handlerName;
        $this->handlerName = $forward['task'] ?? $this->handlerName;

        // Check if we need to forward to another action
        $this->actionName = $forward['action'] ?? $this->actionName;

        // Check if we need to forward changing the current parameters
        $this->params = $forward['params'] ?? $this->params;

        $this->finished  = false;
        $this->forwarded = true;
    }

    /**
     * Gets the latest dispatched action name
     *
     * @return string
     */
    public function getActionName(): string
    {
        return $this->actionName;
    }

    /**
     * Gets the default action suffix
     *
     * @return string
     */
    public function getActionSuffix(): string
    {
        return $this->actionSuffix;
    }

    /**
     * Returns the current method to be/executed in the dispatcher
     *
     * @return string
     */
    public function getActiveMethod(): string
    {
        if (true !== isset($this->activeMethodMap[$this->actionName])) {
            $activeMethodName = lcfirst($this->toCamelize($this->actionName));

            $this->activeMethodMap[$this->actionName] = $activeMethodName;
        }

        return $this->activeMethodMap[$this->actionName] . $this->actionSuffix;
    }

    /**
     * Returns bound models from binder instance
     *
     * ```php
     * class UserController extends Controller
     * {
     *     public function showAction(User $user)
     *     {
     *         // return array with $user
     *         $boundModels = $this->dispatcher->getBoundModels();
     *     }
     * }
     * ```
     *
     * @return array
     */
    public function getBoundModels(): array
    {
        if (null === $this->modelBinder) {
            return [];
        }

        return $this->modelBinder->getBoundModels();
    }

    /**
     * Returns the default namespace
     *
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        return $this->defaultNamespace;
    }

    /**
     * Possible class name that will be located to dispatch the request
     *
     * @return string
     */
    public function getHandlerClass(): string
    {
        $this->resolveEmptyProperties();
        $handlerClass = '';

        // We don't camelize the classes if they are in namespaces
        $camelizedClass = $this->handlerName;
        if (false !== mb_strpos($this->handlerName, "\\")) {
            $camelizedClass = $this->toCamelize($this->handlerName);
        }


        // Create the complete controller class name prepending the namespace
        if (null !== $this->namespaceName) {
            $handlerClass = $this->namespaceName;
            if (true !== $this->toEndsWith($this->namespaceName, "\\")) {
                $handlerClass .= "\\";
            }
        }

        return $handlerClass . $camelizedClass . $this->handlerSuffix;
    }

    /**
     * Gets the default handler suffix
     *
     * @return string
     */
    public function getHandlerSuffix(): string
    {
        return $this->handlerSuffix;
    }

    /**
     * Gets model binder
     *
     * @return BinderInterface|null
     */
    public function getModelBinder(): ?BinderInterface
    {
        return $this->modelBinder;
    }

    /**
     * Gets the module where the controller class is
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * Gets a namespace to be prepended to the current handler name
     */
    public function getNamespaceName(): string
    {
        return $this->namespaceName;
    }

    /**
     * Gets a param by its name or numeric index
     *
     * @param mixed param
     * @param string|array filters
     * @param mixed defaultValue
     *
     * @return mixed
     */
    public function getParam($param, $filters = null, $defaultValue = null)
    {
        if (true !== isset($this->params[$param])) {
            return $defaultValue;
        }

        if (null === $filters) {
            return $this->params[$param];
        }

        if (null === $this->container) {
            $this->throwDispatchException(
                'filter service',
                Exception::EXCEPTION_NO_DI
            );
        }

        $filter = $this->container->getShared('filter');

        return $filter->sanitize($this->params[$param], $filters);
    }

    /**
     * Gets action params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Returns value returned by the latest dispatched action
     *
     * @return mixed
     */
    public function getReturnedValue()
    {
        return $this->returnedValue;
    }

    /**
     * Check if a param exists
     *
     * @param mixed $param
     *
     * @return bool
     */
    public function hasParam($param): bool
    {
        return isset($this->params[$param]);
    }

    /**
     * Checks if the dispatch loop is finished or has more pendent
     * controllers/tasks to dispatch
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }

    /**
     * Sets the action name to be dispatched
     *
     * @param string $actionName
     *
     * @return DispatcherInterface
     */
    public function setActionName(string $actionName): DispatcherInterface
    {
        $this->actionName = $actionName;

        return $this;
    }

    /**
     * Sets the default action suffix
     *
     * @param string $actionSuffix
     *
     * @return DispatcherInterface
     */
    public function setActionSuffix(string $actionSuffix): DispatcherInterface
    {
        $this->actionSuffix = $actionSuffix;

        return $this;
    }

    /**
     * Sets the default action name
     *
     * @param string $actionName
     *
     * @return DispatcherInterface
     */
    public function setDefaultAction(string $actionName): DispatcherInterface
    {
        $this->defaultAction = $actionName;

        return $this;
    }

    /**
     * Sets the default namespace
     *
     * @param string $namespaceName
     *
     * @return DispatcherInterface
     */
    public function setDefaultNamespace(string $namespaceName): DispatcherInterface
    {
        $this->defaultNamespace = $namespaceName;

        return $this;
    }

    /**
     * Sets the default suffix for the handler
     *
     * @param string $handlerSuffix
     *
     * @return DispatcherInterface
     */
    public function setHandlerSuffix(string $handlerSuffix): DispatcherInterface
    {
        $this->handlerSuffix = $handlerSuffix;

        return $this;
    }

    /**
     * Enable model binding during dispatch
     *
     * ```php
     * $di->set(
     *     'dispatcher',
     *     function() {
     *         $dispatcher = new Dispatcher();
     *
     *         $dispatcher->setModelBinder(
     *             new Binder(),
     *             'cache'
     *         );
     *
     *         return $dispatcher;
     *     }
     * );
     * ```
     *
     * @param BinderInterface     $modelBinder
     * @param CacheInterface|null $cache
     *
     * @return DispatcherInterface
     */
    public function setModelBinder(
        BinderInterface $modelBinder,
        CacheInterface $cache = null
    ): DispatcherInterface {
        if (null !== $cache) {
            $modelBinder->setCache($cache);
        }


        $this->modelBinding = true;
        $this->modelBinder  = $modelBinder;

        return $this;
    }

    /**
     * Sets the module where the controller is (only informative)
     *
     * @param string $moduleName
     *
     * @return DispatcherInterface
     */
    public function setModuleName(string $moduleName): DispatcherInterface
    {
        $this->moduleName = $moduleName;

        return $this;
    }

    /**
     * Sets the namespace where the controller class is
     *
     * @param string $namespaceName
     *
     * @return DispatcherInterface
     */
    public function setNamespaceName(string $namespaceName): DispatcherInterface
    {
        $this->namespaceName = $namespaceName;

        return $this;
    }

    /**
     * Set a param by its name or numeric index
     *
     * @param mixed $param
     * @param mixed $value
     *
     * @return DispatcherInterface
     */
    public function setParam($param, $value): DispatcherInterface
    {
        $this->params[$param] = $value;

        return $this;
    }

    /**
     * Sets action params to be dispatched
     *
     * @param array $params
     *
     * @return DispatcherInterface
     */
    public function setParams(array $params): DispatcherInterface
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Sets the latest returned value by an action manually
     *
     * @param mixed $value
     *
     * @return DispatcherInterface
     */
    public function setReturnedValue($value): DispatcherInterface
    {
        $this->returnedValue = $value;

        return $this;
    }

    /**
     * Check if the current executed action was forwarded by another one
     *
     * @return bool
     */
    public function wasForwarded(): bool
    {
        return $this->forwarded;
    }

    /**
     * @param BaseException $exception
     *
     * @return mixed
     */
    abstract protected function handleException(BaseException $exception);

    /**
     * @param string $message
     * @param int    $exceptionCode
     *
     * @return mixed
     */
    abstract protected function throwDispatchException(
        string $message,
        int $exceptionCode = 0
    );

    /**
     * Set empty properties to their defaults (where defaults are available)
     */
    protected function resolveEmptyProperties(): void
    {
        // If the current namespace is null we use the default namespace
        if (null === $this->namespaceName) {
            $this->namespaceName = $this->defaultNamespace;
        }

        // If the handler is null we use the default handler
        if (null === $this->handlerName) {
            $this->handlerName = $this->defaultHandler;
        }

        // If the action is null we use the default action
        if (null === $this->actionName) {
            $this->actionName = $this->defaultAction;
        }
    }
}
