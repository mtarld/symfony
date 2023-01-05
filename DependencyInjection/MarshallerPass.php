<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class MarshallerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('marshaller')) {
            return;
        }

        $marshallerDefinition = $container->getDefinition('marshaller');

        $marshallerDefinition->replaceArgument(1, $this->findAndSortTaggedServices('marshaller.context.builder.marshal', $container));
        $marshallerDefinition->replaceArgument(2, $this->findAndSortTaggedServices('marshaller.context.builder.generation', $container));
        $marshallerDefinition->replaceArgument(3, $this->findAndSortTaggedServices('marshaller.context.builder.unmarshal', $container));
    }
}
