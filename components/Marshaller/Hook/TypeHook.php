<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

/**
 * @internal
 */
final class TypeHook
{
    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(string $type, string $accessor, string $format, array $context): string
    {
        $symfonyContext = $context['symfony'] ?? [];

        if (!isset($symfonyContext['type_extractor'])) {
            throw new \RuntimeException("Missing \"\$context['symfony']['type_extractor']\".");
        }

        if (null !== $formatter = ($symfonyContext['type_value_formatter'][$type] ?? null)) {
            $accessor = sprintf('$context[\'symfony\'][\'type_value_formatter\'][\'%s\'](%s, $context)', $type, $accessor);
            $type = $symfonyContext['type_extractor']->extractFromReturnType(new \ReflectionFunction(\Closure::fromCallable($formatter)));
        }

        return $context['type_value_template_generator']($type, $accessor, $context);
    }
}
