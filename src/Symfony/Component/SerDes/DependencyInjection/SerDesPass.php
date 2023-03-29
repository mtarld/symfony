<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class SerDesPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ser_des.serializer')) {
            return;
        }

        $container->getDefinition('ser_des.serializer')
            ->replaceArgument(0, $this->findAndSortTaggedServices('ser_des.context_builder.serialize', $container))
            ->replaceArgument(1, $this->findAndSortTaggedServices('ser_des.context_builder.deserialize', $container));

        $container->getDefinition('ser_des.context_builder.serialize.hook')
            ->replaceArgument(0, $this->findAndSortTaggedServices(new TaggedIteratorArgument('ser_des.hook.serialize', 'name'), $container));

        $container->getDefinition('ser_des.context_builder.deserialize.hook')
            ->replaceArgument(0, $this->findAndSortTaggedServices(new TaggedIteratorArgument('ser_des.hook.deserialize', 'name'), $container));
    }
}
