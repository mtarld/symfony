<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\NativeContextBuilderInterface;
use Symfony\Component\Marshaller\Type\PhpstanTypeExtractor;

final class PhpstanHookNativeContextBuilder implements NativeContextBuilderInterface
{
    public function __construct(
        private readonly PhpstanTypeExtractor $typeExtractor,
    ) {
    }

    public function build(string $format, array $nativeContext): array
    {
        $nativeContext['hooks']['property'] = function (\ReflectionProperty $property, string $accessor, string $format, array $context): ?string {
            if (null === $type = $this->typeExtractor->extractFromProperty($property)) {
                return null;
            }

            $context['enclosed'] = false;
            unset($context['hooks']['property']);

            return marshal_generate($type, $format, $context);
        };

        $nativeContext['hooks']['function'] = function (\ReflectionFunction $function, string $accessor, string $format, array $context): ?string {
            if (null === $type = $this->typeExtractor->extractFromReturnType($function)) {
                return null;
            }

            $context['enclosed'] = false;
            unset($context['hooks']['function']);

            return marshal_generate($type, $format, $context);
        };

        return $nativeContext;
    }
}
