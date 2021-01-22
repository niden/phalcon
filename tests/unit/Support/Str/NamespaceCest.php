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

namespace Phalcon\Tests\Unit\Support\Str;

use Codeception\Example;
use Phalcon\Tests\Fixtures\Support\Str\NamespaceFixture;
use UnitTester;

/**
 * Class NamespaceCest
 *
 * @package Phalcon\Tests\Unit\Support\Str
 */
class NamespaceCest
{
    /**
     * Tests Phalcon\Support\Str :: namespaceFromClass()
     *
     * @dataProvider getNamespaceSources
     *
     * @param UnitTester $I
     * @param Example    $example
     *
     * @author Phalcon Team <team@phalcon.io>
     * @since  2020-09-09
     */
    public function supportStrNamespaceFromClass(UnitTester $I, Example $example)
    {
        $I->wantToTest('Support\Str - namespaceFromClass - ' . $example[0]);

        $object    = new NamespaceFixture();
        $value     = $example[0];
        $expected  = $example[1];
        $actual    = $object->namespaceFromClass($value);
        $I->assertEquals($expected, $actual);
    }

    /**
     * Tests Phalcon\Support\Str :: namespaceFromClass()
     *
     * @dataProvider getClassSources
     *
     * @param UnitTester $I
     * @param Example    $example
     *
     * @author Phalcon Team <team@phalcon.io>
     * @since  2020-09-09
     */
    public function supportStrClassFromNamespace(UnitTester $I, Example $example)
    {
        $I->wantToTest('Support\Str - classFromNamespace - ' . $example[0]);

        $object    = new NamespaceFixture();
        $value     = $example[0];
        $expected  = $example[1];
        $actual    = $object->classFromNamespace($value);
        $I->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    private function getNamespaceSources(): array
    {
        return [
            ['Acme\Models\User', 'Acme\Models'],
            ['', null],
            ['Acme\Models\User\\', 'Acme\Models'],
        ];
    }

    /**
     * @return array
     */
    private function getClassSources(): array
    {
        return [
            ['Acme\Models\User', 'User'],
            ['', null],
            ['Acme\Models\User\\', 'User'],
        ];
    }
}
