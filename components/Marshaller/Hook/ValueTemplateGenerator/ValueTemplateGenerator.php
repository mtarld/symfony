<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\ValueTemplateGenerator;

use Symfony\Component\Marshaller\Type\Type;

final class ValueTemplateGenerator
{
    /**
     * @param array<string, mixed> $context
     */
    public static function generate(Type $type, string $accessor, array $context): string
    {
        return match ($format) {
            'json' => JsonValueTemplateGenerator::generate($type, $accessor, $context),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format)),
        };
    }
}
