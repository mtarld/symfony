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
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\JsonMarshaller\DependencyInjection\RuntimeMarshallerServicesPass;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithAttributesUsingServices;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithFormatterAttributes;
use Symfony\Component\JsonMarshaller\Type\TypeExtractorInterface;

class RuntimeMarshallerServicesPassTest extends TestCase
{
    public function testRetrieveServices()
    {
        $container = new ContainerBuilder();

        $container->register('marshaller.json.marshaller')->setArguments([null, null, null]);
        $container->register('marshaller.json.unmarshaller')->setArguments([null, null, null]);
        $container->register('.marshaller.marshal.data_model_builder')->setArguments([null, null]);
        $container->register('.marshaller.unmarshal.data_model_builder')->setArguments([null, null]);

        $container->register(ClassicDummy::class, ClassicDummy::class)->addTag('marshaller.marshallable');
        $container->register(DummyWithFormatterAttributes::class, DummyWithFormatterAttributes::class)->addTag('marshaller.marshallable');
        $container->register(DummyWithAttributesUsingServices::class, DummyWithAttributesUsingServices::class)->addTag('marshaller.marshallable');

        (new RuntimeMarshallerServicesPass())->process($container);

        $runtimeServicesId = $container->getDefinition('marshaller.json.marshaller')->getArgument(2);

        $this->assertSame($runtimeServicesId, $container->getDefinition('marshaller.json.unmarshaller')->getArgument(2));
        $this->assertSame($runtimeServicesId, $container->getDefinition('.marshaller.marshal.data_model_builder')->getArgument(1));
        $this->assertSame($runtimeServicesId, $container->getDefinition('.marshaller.unmarshal.data_model_builder')->getArgument(1));

        $runtimeServices = $container->getDefinition($runtimeServicesId)->getArgument(0);

        $runtimeService = $runtimeServices[sprintf('%s::serviceAndConfig[service]', DummyWithAttributesUsingServices::class)];
        $this->assertInstanceOf(ServiceClosureArgument::class, $runtimeService);
        $this->assertEquals([new TypedReference(TypeExtractorInterface::class, TypeExtractorInterface::class, name: 'service')], $runtimeService->getValues());

        $runtimeService = $runtimeServices[sprintf('%s::autowireAttribute[service]', DummyWithAttributesUsingServices::class)];
        $this->assertInstanceOf(ServiceClosureArgument::class, $runtimeService);
        $this->assertEquals([new Reference('custom_service')], $runtimeService->getValues());

        $this->assertArrayNotHasKey(sprintf('%s::skippedUnknownService[skipped]', DummyWithAttributesUsingServices::class), $runtimeServices);

        $this->assertArrayNotHasKey(sprintf('%s::serviceAndConfig[config]', DummyWithAttributesUsingServices::class), $runtimeServices);

        $this->assertArrayNotHasKey(sprintf('%s::serviceAndConfig[value]', DummyWithAttributesUsingServices::class), $runtimeServices);
        $this->assertArrayNotHasKey(sprintf('%s::autowireAttribute[value]', DummyWithAttributesUsingServices::class), $runtimeServices);
        $this->assertArrayNotHasKey(sprintf('%s::skippedUnknownService[value]', DummyWithAttributesUsingServices::class), $runtimeServices);
    }
}
