<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Encoder\DependencyInjection\EncoderPass;
use Symfony\Component\Encoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithAttributesUsingServices;
use Symfony\Component\Encoder\Tests\Fixtures\Model\DummyWithFormatterAttributes;

class EncoderPassTest extends TestCase
{
    public function testInjectEncodableClassNames()
    {
        $container = new ContainerBuilder();

        $container->register('.encoder.cache_warmer.lazy_ghost')->setArguments([null]);
        $container->register('.encoder.encode.data_model_builder')->setArguments([null, null]);
        $container->register('.encoder.decode.data_model_builder')->setArguments([null, null]);

        $container->register(ClassicDummy::class, ClassicDummy::class)->addTag('encoder.encodable');
        $container->register(DummyWithFormatterAttributes::class, DummyWithFormatterAttributes::class)->addTag('encoder.encodable');
        $container->register(DummyWithAttributesUsingServices::class, DummyWithAttributesUsingServices::class)->addTag('encoder.encodable');

        (new EncoderPass())->process($container);

        $this->assertSame([
            ClassicDummy::class,
            DummyWithFormatterAttributes::class,
            DummyWithAttributesUsingServices::class,
        ], $container->getDefinition('.encoder.cache_warmer.lazy_ghost')->getArgument(0));
    }
}
