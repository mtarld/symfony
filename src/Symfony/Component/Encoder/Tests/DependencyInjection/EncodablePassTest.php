<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Encoder\DependencyInjection\EncodablePass;
use Symfony\Component\Encoder\Tests\Fixtures\Annotations\AbstractDummy;
use Symfony\Component\Encoder\Tests\Fixtures\Model\ClassicDummy;

class EncodablePassTest extends TestCase
{
    public function testFindEncodableClasses()
    {
        $container = new ContainerBuilder();
        $container->setParameter('encoder.encodable_paths', [\dirname(__DIR__, 1).'/Fixtures/{Model,Invalid}']);

        (new EncodablePass())->process($container);

        $encodable = [];
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag('encoder.encodable')) {
                continue;
            }

            $encodable[] = $definition->getClass();
        }

        $this->assertContains(ClassicDummy::class, $encodable);
        $this->assertNotContains(AbstractDummy::class, $encodable);
    }
}
