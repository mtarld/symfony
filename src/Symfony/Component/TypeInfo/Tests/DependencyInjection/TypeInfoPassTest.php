<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\TypeInfo\DependencyInjection\TypeInfoPass;

class TypeInfoPassTest extends TestCase
{
    public function testInjectTypeResolvers()
    {
        $container = new ContainerBuilder();

        $container->register('type_info.resolver')->setArguments([null]);

        $container->register('second')->addTag('type_info.resolver', ['priority' => 10]);
        $container->register('third')->addTag('type_info.resolver', ['priority' => 1]);
        $container->register('first')->addTag('type_info.resolver', ['priority' => 100]);

        (new TypeInfoPass())->process($container);

        $this->assertEquals(
            new IteratorArgument([new Reference('first'), new Reference('second'), new Reference('third')]),
            $container->getDefinition('type_info.resolver')->getArgument(0),
        );
    }
}
