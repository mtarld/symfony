<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\PhpstanType;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\NativeContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\PhpstanType\PhpstanTypeExtractor;

use function Symfony\Component\Marshaller\marshal_generate;

final class PhpstanTypeHookNativeContextBuilder implements NativeContextBuilderInterface
{
    private readonly PhpstanTypeExtractor $typeExtractor;

    public function __construct()
    {
        $this->typeExtractor = new PhpstanTypeExtractor();
    }

    public function build(string $format, Context $context, array $nativeContext): array
    {
        $nativeContext['hooks']['property'] = function (\ReflectionProperty $property, string $accessor, string $format, array $context): ?string {
            if (null === $type = $this->typeExtractor->extractFromProperty($property)) {
                return null;
            }

            $context['enclosed'] = false;
            $context['accessor'] = $accessor;

            unset($context['hooks']['property']);

            return $context['property_name_generator']($property, $context['property_separator'], $context)
                .marshal_generate($type, $format, $context);
        };

        $nativeContext['hooks']['function'] = function (\ReflectionFunction $function, string $accessor, string $format, array $context): ?string {
            if (null === $type = $this->typeExtractor->extractFromReturnType($function)) {
                return null;
            }

            $context['enclosed'] = false;
            $context['accessor'] = $accessor;

            unset($context['hooks']['function']);

            return marshal_generate($type, $format, $context);
        };

        return $nativeContext;
    }
}
