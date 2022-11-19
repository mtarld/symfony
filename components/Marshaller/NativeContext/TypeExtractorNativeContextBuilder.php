<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Type\TypeExtractor;

final class TypeExtractorNativeContextBuilder
{
    public function __construct(
        private readonly TypeExtractor $typeExtractor,
    ) {
    }

    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        $nativeContext['symfony']['type_extractor'] = $this->typeExtractor;

        return $nativeContext;
    }
}
