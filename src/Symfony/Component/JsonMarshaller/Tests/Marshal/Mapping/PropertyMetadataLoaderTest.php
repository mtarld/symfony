<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\Marshal\Mapping;

use PHPUnit\Framework\TestCase;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\JsonMarshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadata;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadataLoader;

class PropertyMetadataLoaderTest extends TestCase
{
    public function testExtractPropertyMetadata()
    {
        $loader = new PropertyMetadataLoader(new PhpstanTypeExtractor(new ReflectionTypeExtractor()));

        $this->assertEquals([
            'id' => new PropertyMetadata('id', Type::int(), []),
            'name' => new PropertyMetadata('name', Type::string(), []),
        ], $loader->load(ClassicDummy::class, [], []));
    }
}
