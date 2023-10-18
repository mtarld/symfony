<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Encoder\DecoderInterface;
use Symfony\Component\Encoder\EncoderInterface;
use Symfony\Component\Json\DependencyInjection\JsonPass;
use Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithAttributesUsingServices;
use Symfony\Component\Json\Tests\Fixtures\Model\DummyWithFormatterAttributes;

class JsonPassTest extends TestCase
{
    public function testInjectEncodableClassNames()
    {
        $container = new ContainerBuilder();

        $container->register('json.encoder');
        $container->register('.json.cache_warmer.template')->setArguments([null]);
        $container->register('json.decoder.eager')->setArguments([null, null, null, null]);
        $container->register('json.decoder.lazy')->setArguments([null, null, null, null]);

        $container->register(ClassicDummy::class, ClassicDummy::class)->addTag('encoder.encodable');
        $container->register(DummyWithFormatterAttributes::class, DummyWithFormatterAttributes::class)->addTag('encoder.encodable');
        $container->register(DummyWithAttributesUsingServices::class, DummyWithAttributesUsingServices::class)->addTag('encoder.encodable');

        (new JsonPass())->process($container);

        $this->assertSame([
            ClassicDummy::class,
            DummyWithFormatterAttributes::class,
            DummyWithAttributesUsingServices::class,
        ], $container->getDefinition('.json.cache_warmer.template')->getArgument(0));
    }

    public function testRegisterAliases()
    {
        $container = new ContainerBuilder();

        $container->register('json.encoder');
        $container->register('.json.cache_warmer.template')->setArguments([null]);

        (new JsonPass())->process($container);

        $this->assertEquals('json.encoder', (string) $container->getAlias(sprintf('%s $jsonEncoder', EncoderInterface::class)));
        $this->assertEquals('json.decoder', (string) $container->getAlias(sprintf('%s $jsonDecoder', DecoderInterface::class)));
    }
}
