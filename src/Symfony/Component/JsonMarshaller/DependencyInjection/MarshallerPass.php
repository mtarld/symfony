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
use Symfony\Component\JsonMarshaller\JsonUnmarshaller;
use Symfony\Component\JsonMarshaller\MarshallerInterface;
use Symfony\Component\JsonMarshaller\UnmarshallerInterface;

/**
 * Injects marshallable classes list into related services
 * and defines basic aliases.
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

        $defaultUnmarshallerId = $container->getParameter('marshaller.lazy_unmarshal')
            ? 'marshaller.json.unmarshaller.lazy'
            : 'marshaller.json.unmarshaller.eager';

        $container->setAlias(JsonUnmarshaller::class, $defaultUnmarshallerId);

        $container->registerAliasForArgument('marshaller.json.marshaller', MarshallerInterface::class, 'json.marshaller');
        $container->registerAliasForArgument($defaultUnmarshallerId, UnmarshallerInterface::class, 'json.unmarshaller');
    }
}
