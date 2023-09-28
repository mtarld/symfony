<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\JsonMarshaller\DependencyInjection\MarshallablePass;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Annotations\AbstractDummy;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy;

class MarshallablePassTest extends TestCase
{
    public function testFindMarshallableClasses()
    {
        $container = new ContainerBuilder();
        $container->register('marshaller.json.marshaller');
        $container->setParameter('marshaller.marshallable_paths', [\dirname(__DIR__, 1).'/Fixtures/{Dto,Invalid}']);

        (new MarshallablePass())->process($container);

        $marshallable = [];
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag('marshaller.marshallable')) {
                continue;
            }

            $marshallable[] = $definition->getClass();
        }

        $this->assertContains(ClassicDummy::class, $marshallable);
        $this->assertNotContains(AbstractDummy::class, $marshallable);
    }
}
