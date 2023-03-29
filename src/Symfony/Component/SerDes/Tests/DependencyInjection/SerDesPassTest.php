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

        $serializerDefinition = $container->register('ser_des.serializer')->setArguments([null, null]);
        $serializeHookContextBuilderDefinition = $container->register('ser_des.context_builder.serialize.hook')->setArguments([null]);
        $deserializeHookContextBuilderDefinition = $container->register('ser_des.context_builder.deserialize.hook')->setArguments([null]);

        $container->register('s2')
            ->addTag('ser_des.context_builder.serialize', ['priority' => 100])
            ->addTag('ser_des.hook.serialize', ['priority' => 100]);

        $container->register('s1')
            ->addTag('ser_des.context_builder.serialize', ['priority' => 200])
            ->addTag('ser_des.hook.serialize', ['priority' => 200]);

        $container->register('s3')
            ->addTag('ser_des.context_builder.serialize')
            ->addTag('ser_des.hook.serialize');

        $container->register('d2')
            ->addTag('ser_des.context_builder.deserialize', ['priority' => 100])
            ->addTag('ser_des.hook.deserialize', ['priority' => 100]);

        $container->register('d1')
            ->addTag('ser_des.context_builder.deserialize', ['priority' => 200])
            ->addTag('ser_des.hook.deserialize', ['priority' => 200]);

        $container->register('d3')
            ->addTag('ser_des.context_builder.deserialize')
            ->addTag('ser_des.hook.deserialize');

        (new SerDesPass())->process($container);

        $this->assertEquals([new Reference('s1'), new Reference('s2'), new Reference('s3')], $serializerDefinition->getArgument(0));
        $this->assertEquals([new Reference('d1'), new Reference('d2'), new Reference('d3')], $serializerDefinition->getArgument(1));
        $this->assertEquals(['s1' => new Reference('s1'), 's2' => new Reference('s2'), 's3' => new Reference('s3')], $serializeHookContextBuilderDefinition->getArgument(0));
        $this->assertEquals(['d1' => new Reference('d1'), 'd2' => new Reference('d2'), 'd3' => new Reference('d3')], $deserializeHookContextBuilderDefinition->getArgument(0));
    }
}
