<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\JsonEncoder\DecoderInterface;
use Symfony\Component\JsonEncoder\DependencyInjection\JsonEncoderPass;
use Symfony\Component\JsonEncoder\EncoderInterface;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\AbstractDummy;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithFormatterAttributes;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithPhpDoc;

class JsonEncoderPassTest extends TestCase
{
    public function testInjectEncodableClassNames()
    {
        $container = new ContainerBuilder();
        $container->setParameter('json_encoder.encodable_paths', [\dirname(__DIR__, 1).'/Fixtures/{Model,Invalid}']);

        $container->register('json_encoder.encoder');
        $container->register('.json_encoder.cache_warmer.encoder_decoder')->setArguments([null]);
        $container->register('.json_encoder.cache_warmer.lazy_ghost')->setArguments([null]);

        (new JsonEncoderPass())->process($container);

        $encodableClasses = [ClassicDummy::class, DummyWithFormatterAttributes::class, DummyWithPhpDoc::class];

        $this->assertContains(ClassicDummy::class, $container->getDefinition('.json_encoder.cache_warmer.encoder_decoder')->getArgument(0));
        $this->assertNotContains(AbstractDummy::class, $container->getDefinition('.json_encoder.cache_warmer.encoder_decoder')->getArgument(0));
    }

    public function testRegisterAliases()
    {
        $container = new ContainerBuilder();

        $container->register('json_encoder.encoder');
        $container->register('.json_encoder.cache_warmer.encoder_decoder')->setArguments([null]);
        $container->register('.json_encoder.cache_warmer.lazy_ghost')->setArguments([null]);

        (new JsonEncoderPass())->process($container);

        $this->assertEquals('json_encoder.encoder', (string) $container->getAlias(sprintf('%s $jsonEncoder', EncoderInterface::class)));
        $this->assertEquals('json_encoder.decoder', (string) $container->getAlias(sprintf('%s $jsonDecoder', DecoderInterface::class)));
    }
}
