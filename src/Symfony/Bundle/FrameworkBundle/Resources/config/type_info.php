<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\TypeInfo\Resolver\ChainTypeResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionParameterResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionPropertyResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionReturnResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionTypeResolver;
use Symfony\Component\TypeInfo\Resolver\TypeResolverInterface;

/*
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
return static function (ContainerConfigurator $container) {
    $container->services()
        ->set('type_info.resolver.reflection_type', ReflectionTypeResolver::class)

        ->set('type_info.resolver.reflection_property', ReflectionPropertyResolver::class)
            ->args([
                service('type_info.resolver.reflection_type'),
            ])

        ->set('type_info.resolver.reflection_return', ReflectionReturnResolver::class)
            ->args([
                service('type_info.resolver.reflection_type'),
            ])

        ->set('type_info.resolver.reflection_parameter', ReflectionParameterResolver::class)
            ->args([
                service('type_info.resolver.reflection_type'),
            ])

        ->set('type_info.resolver', ChainTypeResolver::class)
            ->args([[
                service('type_info.resolver.reflection_type'),
                service('type_info.resolver.reflection_property'),
                service('type_info.resolver.reflection_return'),
                service('type_info.resolver.reflection_parameter'),
            ]])

        ->alias(TypeResolverInterface::class, 'type_info.resolver')
    ;
};
