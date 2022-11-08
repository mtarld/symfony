<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\ValueTemplateGenerator;

use Symfony\Component\Marshaller\Type\Types;

final class ValueTemplateGenerator
{
    /**
     * @param array<string, mixed> $context
     */
    public static function generate(Types $types, string $accessor, string $format, array $context): string
    {
        return match ($format) {
            'json' => JsonValueTemplateGenerator::generate($types, $accessor, $context),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format)),
        };
    }
}
