<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\SerDes\DependencyInjection\SerDesPass;

class SerDesPassTest extends TestCase
{
    public function testContextBuildersAreOrderedAccordingToPriority()
    {
        $container = new ContainerBuilder();

        $definition = $container->register('ser_des.serializer')->setArguments([null]);

        $container->register('n2')->addTag('ser_des.context_builder', ['priority' => 100]);
        $container->register('n1')->addTag('ser_des.context_builder', ['priority' => 200]);
        $container->register('n3')->addTag('ser_des.context_builder');

        (new SerDesPass())->process($container);

        $this->assertEquals([new Reference('n1'), new Reference('n2'), new Reference('n3')], $definition->getArgument(0));
    }
}
