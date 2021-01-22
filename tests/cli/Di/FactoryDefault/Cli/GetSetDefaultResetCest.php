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

namespace Phalcon\Tests\Cli\Di\FactoryDefault\Cli;

use CliTester;
use Phalcon\Di\FactoryDefault\Cli as Di;
use function spl_object_hash;

class GetSetDefaultResetCest
{
    /**
     * Tests Phalcon\Di\FactoryDefault\Cli :: getDefault()/setDefault()/reset()
     *
     * @author Phalcon Team <team@phalcon.io>
     * @since  2018-11-13
     */
    public function diFactorydefaultCliGetSetDefaultReset(CliTester $I)
    {
        $I->wantToTest('Di\FactoryDefault\Cli - getDefault()/setDefault()/reset()');

        // there is a DI container
        $I->assertInstanceOf(Di::class, Di::getDefault());

        $container1 = Di::getDefault();
        $hash1 = spl_object_hash($container1);

        // delete it
        Di::reset();

        $container2 = Di::getDefault();
        $hash2 = spl_object_hash($container2);
        $I->assertNotEquals($hash1, $hash2);

        // delete it
        Di::reset();

        // set it again
        Di::setDefault($container1);
        $actual = Di::getDefault();
        $I->assertEquals($hash1, spl_object_hash($actual));



    }
}
