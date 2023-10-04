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

use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\ChainTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\ReflectionParameterTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\ReflectionPropertyTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\ReflectionReturnTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\ReflectionTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        // type context
        ->set('type_info.type_context_factory', TypeContextFactory::class)
            ->args([service('type_info.resolver.string')->nullOnInvalid()])

        // type resolvers
        ->set('type_info.resolver', ChainTypeResolver::class)
            ->args([[]])
        ->alias(TypeResolverInterface::class, 'type_info.resolver')

        ->set('type_info.resolver.reflection_type', ReflectionTypeResolver::class)
            ->args([service('type_info.type_context_factory')])
            ->tag('type_info.resolver', ['priority' => -1001])
        ->alias(ReflectionTypeResolver::class, 'type_info.resolver.reflection_type')

        ->set('type_info.resolver.reflection_parameter', ReflectionParameterTypeResolver::class)
            ->args([service('type_info.resolver.reflection_type'), service('type_info.type_context_factory')])
            ->tag('type_info.resolver', ['priority' => -1002])
        ->alias(ReflectionParameterTypeResolver::class, 'type_info.resolver.reflection_parameter')

        ->set('type_info.resolver.reflection_property', ReflectionPropertyTypeResolver::class)
            ->args([service('type_info.resolver.reflection_type'), service('type_info.type_context_factory')])
            ->tag('type_info.resolver', ['priority' => -1003])
        ->alias(ReflectionPropertyTypeResolver::class, 'type_info.resolver.reflection_property')

        ->set('type_info.resolver.reflection_return', ReflectionReturnTypeResolver::class)
            ->args([service('type_info.resolver.reflection_type'), service('type_info.type_context_factory')])
            ->tag('type_info.resolver', ['priority' => -1004])
        ->alias(ReflectionReturnTypeResolver::class, 'type_info.resolver.reflection_return')
    ;
};
