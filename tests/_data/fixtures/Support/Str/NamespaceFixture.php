<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Framework.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace Phalcon\Tests\Fixtures\Support\Str;

use Phalcon\Support\Str\Traits\NamespaceTrait;

/**
 * Class NamespaceFixture
 *
 * @package Phalcon\Tests\Fixtures\Support\Str
 */
class NamespaceFixture
{
    use NamespaceTrait;

    /**
     * @param string $className
     *
     * @return string|null
     */
    public function classFromNamespace(string $className): ?string
    {
        return $this->getClassFromNamespace($className);
    }

    /**
     * @param string $className
     *
     * @return string|null
     */
    public function namespaceFromClass(string $className): ?string
    {
        return $this->getNamespaceFromClass($className);
    }
}
