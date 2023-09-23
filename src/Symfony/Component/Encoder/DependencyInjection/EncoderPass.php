<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Injects encodable classes into services.
 *
 * @author Mathias Arlaud<mathias.arlaud@gmail.com>
 */
final readonly class EncoderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('.encoder.cache_warmer.lazy_ghost')) {
            return;
        }

        $encodableClassNames = array_map(
            fn (string $id) => $container->getDefinition($id)->getClass(),
            array_keys($container->findTaggedServiceIds('encoder.encodable')),
        );

        $container->getDefinition('.encoder.cache_warmer.lazy_ghost')
            ->replaceArgument(0, $encodableClassNames);
    }
}
