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
        // TODO
        // if (!$container->hasDefinition('serializer.serialize')) {
        //     return;
        // }

        $eagerDeserialize = [];
        foreach ($container->findTaggedServiceIds('serializer.deserialize.eager') as $id => $tags) {
            $tag = reset($tags);
            $eagerDeserialize[$tag['format']] = new Reference($id);
        }

        $lazyDeserialize = [];
        foreach ($container->findTaggedServiceIds('serializer.deserialize.lazy') as $id => $tags) {
            $tag = reset($tags);
            $lazyDeserialize[$tag['format']] = new Reference($id);
        }

        $container->getDefinition('serializer.deserializer')
            ->replaceArgument(0, $eagerDeserialize)
            ->replaceArgument(1, $lazyDeserialize);
    }
}
