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

namespace Phalcon\Mvc\Model;

use Psr\SimpleCache\CacheInterface;

/**
 * Phalcon\Mvc\Model\BinderInterface
 *
 * Interface for Phalcon\Mvc\Model\Binder
 */
interface BinderInterface
{
    /**
     * Bind models into params in proper handler
     *
     * @param object      $handler
     * @param array       $params
     * @param string      $cacheKey
     * @param string|null $methodName
     *
     * @return array
     */
    public function bindToHandler(
        object $handler,
        array $params,
        string $cacheKey,
        string $methodName = null
    ): array;

    /**
     * Gets active bound models
     *
     * @return array
     */
    public function getBoundModels(): array;

    /**
     * Gets cache instance
     */
    public function getCache(): CacheInterface;

    /**
     * Sets cache instance
     */
    public function setCache(CacheInterface $cache): BinderInterface;
}
