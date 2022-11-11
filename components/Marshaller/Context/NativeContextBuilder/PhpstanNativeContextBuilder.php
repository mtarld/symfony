<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;

final class PhpstanNativeContextBuilder implements GenerationNativeContextBuilderInterface
{
    public function __construct(
        private readonly PhpstanTypeExtractor $typeExtractor,
    ) {
    }

    public function forGeneration(string $type, string $format, Context $context, array $nativeContext): array
    {
        $nativeContext['hooks']['property'] = function (\ReflectionProperty $property, string $accessor, string $format, array $context): ?string {
            if (null === $type = $this->typeExtractor->extractFromProperty($property)) {
                return null;
            }

            $context['enclosed'] = false;
            $context['main_accessor'] = $accessor;

            unset($context['hooks']['property']);

            return $context['property_name_generator']($property, $context['property_separator'], $context)
                .marshal_generate($type, $format, $context);
        };

        $nativeContext['hooks']['function'] = function (\ReflectionFunction $function, string $accessor, string $format, array $context): ?string {
            if (null === $type = $this->typeExtractor->extractFromReturnType($function)) {
                return null;
            }

            $context['enclosed'] = false;
            $context['main_accessor'] = $accessor;

            unset($context['hooks']['function']);

            return marshal_generate($type, $format, $context);
        };

        return $nativeContext;
    }
}
