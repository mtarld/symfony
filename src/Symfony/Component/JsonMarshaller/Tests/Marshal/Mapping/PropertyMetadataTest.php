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
use Symfony\Component\JsonMarshaller\Exception\InvalidArgumentException;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadata;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\DummyWithMethods;
use Symfony\Component\JsonMarshaller\Type\Type;

class PropertyMetadataTest extends TestCase
{
    public function testThrowOnNonStaticFormatter()
    {
        $this->expectException(InvalidArgumentException::class);
        new PropertyMetadata('useless', Type::mixed(), [(new DummyWithMethods())->nonStatic(...)]);
    }

    public function testThrowOnNonAnonymousFormatter()
    {
        $this->expectException(InvalidArgumentException::class);
        new PropertyMetadata('useless', Type::mixed(), [fn () => 'useless']);
    }
}
