<?php

/**
 * This file is part of the Phalcon.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Support\Str\Traits;

use InvalidArgumentException;
use function array_key_last;
use function array_pop;
use function array_slice;
use function explode;
use function get_class;
use function implode;
use function is_string;
use function rtrim;

/**
 * Trait NamespaceTrait
 *
 * @package Phalcon\Support\Str\Traits
 */
trait NamespaceTrait
{
    /**
     * @param string $className
     *
     * @return string|null
     */
    private function getNamespaceFromClass(string $className): ?string
    {
        if (true === empty($className)) {
            return null;
        }

        $class = explode('\\', rtrim($className, '\\'));

        array_pop($class);

        return implode('\\', $class);
    }

    /**
     * @param string $className
     *
     * @return string|null
     */
    private function getClassFromNamespace(string $className): ?string
    {
        if (true === empty($className)) {
            return null;
        }

        $class  = explode('\\', rtrim($className, '\\'));

        return $class[array_key_last($class)];
    }
}
