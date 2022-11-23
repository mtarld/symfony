<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\NativeContext;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\NativeContext\TypeExtractorNativeContextBuilder;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\Marshaller\Type\TypeExtractor;

final class TypeExtractorNativeContextBuilderTest extends TestCase
{
    public function testAddTypeExtractorToNativeContext(): void
    {
        $typeExtractor = new TypeExtractor(new ReflectionTypeExtractor(), new PhpstanTypeExtractor());

        $this->assertSame([
            'symfony' => [
                'type_extractor' => $typeExtractor,
            ],
        ], (new TypeExtractorNativeContextBuilder($typeExtractor))->buildGenerateNativeContext('useless', new Context(), []));
    }
}
