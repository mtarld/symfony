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
 * Injects marshallable classes list into related services.
 *
 * @author Mathias Arlaud<mathias.arlaud@gmail.com>
 */
final readonly class MarshallerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('marshaller.json.marshaller')) {
            return;
        }

        $marshallable = [];
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag('marshaller.marshallable')) {
                continue;
            }

            $marshallable[] = $definition->getClass();
        }

        $container->getDefinition('.marshaller.json.cache_warmer.template')
            ->replaceArgument(0, $marshallable);

        $container->getDefinition('.marshaller.cache_warmer.lazy_ghost')
            ->replaceArgument(0, $marshallable);
    }
}
