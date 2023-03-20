<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Marshaller\DependencyInjection\MarshallerPass;

class MarshallerPassTest extends TestCase
{
    public function testContextBuildersAreOrderedAccordingToPriority()
    {
        $container = new ContainerBuilder();

        $definition = $container->register('marshaller')->setArguments([null]);

        $container->register('n2')->addTag('marshaller.context_builder', ['priority' => 100]);
        $container->register('n1')->addTag('marshaller.context_builder', ['priority' => 200]);
        $container->register('n3')->addTag('marshaller.context_builder');

        (new MarshallerPass())->process($container);

        $this->assertEquals([new Reference('n1'), new Reference('n2'), new Reference('n3')], $definition->getArgument(0));
    }
}
