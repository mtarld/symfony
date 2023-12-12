<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Injects encodable classes into services.
 *
 * @author Mathias Arlaud<mathias.arlaud@gmail.com>
 */
final readonly class JsonEncoderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // TODO change me
        if (!$container->hasDefinition('.json_encoder.cache_warmer.lazy_ghost')) {
            return;
        }

        $encodableClassNames = array_map(
            fn (string $id) => $container->getDefinition($id)->getClass(),
            array_keys($container->findTaggedServiceIds('json_encoder.encodable')),
        );

        $container->getDefinition('.json_encoder.cache_warmer.lazy_ghost')
            ->replaceArgument(0, $encodableClassNames);
    }
}
