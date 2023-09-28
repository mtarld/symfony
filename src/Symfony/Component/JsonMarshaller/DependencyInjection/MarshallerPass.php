<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * TODO.
 *
 * @author Mathias Arlaud<mathias.arlaud@gmail.com>
 */
final class MarshallerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('marshaller.json.marshaller')) {
            return;
        }

        $serializable = [];
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag('marshaller.marshallable')) {
                continue;
            }

            $serializable[] = $definition->getClass();
        }

        $container->getDefinition('.marshaller.json.cache_warmer.template')
            ->replaceArgument(0, $serializable);

        $container->getDefinition('.marshaller.cache_warmer.lazy_ghost')
            ->replaceArgument(0, $serializable);
    }
}
