<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class SerializeDeserializePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('serializer.serialize')) {
            return;
        }

        $contextBuilderDefinition = $container->getDefinition('.serializer.context_builder');

        $serializeServicesDefinitions = array_map(fn (string $id): Reference => new Reference($id), $container->findTaggedServiceIds('serializer.serialize_service'));
        $deserializeServicesDefinitions = array_map(fn (string $id): Reference => new Reference($id), $container->findTaggedServiceIds('serializer.deserialize_service'));

        $contextBuilderDefinition->replaceArgument(4, new ServiceLocatorArgument($serializeServicesDefinitions));
        $contextBuilderDefinition->replaceArgument(5, new ServiceLocatorArgument($deserializeServicesDefinitions));
    }
}
