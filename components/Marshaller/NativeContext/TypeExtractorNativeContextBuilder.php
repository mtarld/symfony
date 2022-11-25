<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

final class TypeExtractorNativeContextBuilder implements MarshalGenerateNativeContextBuilderInterface
{
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    public function build(string $type, Context $context, array $nativeContext): array
    {
        $nativeContext['symfony']['type_extractor'] = $this->typeExtractor;

        return $nativeContext;
    }
}
