<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Tests;

use Symfony\Component\TypeInfo\Resolver\ChainTypeResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionParameterResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionPropertyResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionReturnResolver;
use Symfony\Component\TypeInfo\Resolver\ReflectionTypeResolver;
use Symfony\Component\TypeInfo\Resolver\TypeResolverInterface;

trait TypeResolverAwareTrait
{
    private static function getTypeResolver(): TypeResolverInterface
    {
        return new ChainTypeResolver([
            new ReflectionPropertyResolver(new ReflectionTypeResolver()),
            new ReflectionParameterResolver(new ReflectionTypeResolver()),
            new ReflectionReturnResolver(new ReflectionTypeResolver()),
            new ReflectionTypeResolver(),
        ]);
    }
}
