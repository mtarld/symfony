<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\JsonMarshaller\DependencyInjection\MarshallerPass;
use Symfony\Component\JsonMarshaller\JsonUnmarshaller;
use Symfony\Component\JsonMarshaller\MarshallerInterface;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithAttributesUsingServices;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\JsonMarshaller\UnmarshallerInterface;

class MarshallerPassTest extends TestCase
{
    public function testInjectMarshallable()
    {
        $container = new ContainerBuilder();

        $container->register('marshaller.json.marshaller');
        $container->register('.marshaller.json.cache_warmer.template')->setArguments([null]);
        $container->register('.marshaller.cache_warmer.lazy_ghost')->setArguments([null]);

        $container->register(ClassicDummy::class, ClassicDummy::class)->addTag('marshaller.marshallable');
        $container->register(DummyWithFormatterAttributes::class, DummyWithFormatterAttributes::class)->addTag('marshaller.marshallable');
        $container->register(DummyWithAttributesUsingServices::class, DummyWithAttributesUsingServices::class)->addTag('marshaller.marshallable');

        $container->setParameter('marshaller.lazy_unmarshal', true);

        (new MarshallerPass())->process($container);

        $this->assertSame([
            ClassicDummy::class,
            DummyWithFormatterAttributes::class,
            DummyWithAttributesUsingServices::class,
        ], $container->getDefinition('.marshaller.json.cache_warmer.template')->getArgument(0));

        $this->assertSame([
            ClassicDummy::class,
            DummyWithFormatterAttributes::class,
            DummyWithAttributesUsingServices::class,
        ], $container->getDefinition('.marshaller.cache_warmer.lazy_ghost')->getArgument(0));

        $this->assertEquals('marshaller.json.unmarshaller.lazy', (string) $container->getAlias(JsonUnmarshaller::class));
        $this->assertEquals('marshaller.json.marshaller', (string) $container->getAlias(sprintf('%s $jsonMarshaller', MarshallerInterface::class)));
        $this->assertEquals('marshaller.json.unmarshaller.lazy', (string) $container->getAlias(sprintf('%s $jsonUnmarshaller', UnmarshallerInterface::class)));
    }
}
