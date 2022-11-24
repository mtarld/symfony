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
        if (!isset($context['symfony']['type_extractor'])) {
            throw new \RuntimeException("Missing \"\$context['symfony']['type_extractor']\".");
        }

        $type = $this->type($type, $context);
        $accessor = $this->accessor($type, $accessor, $context);

        return $context['type_value_template_generator']($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function type(string $type, array $context): string
    {
        if (null !== $formatter = ($context['symfony']['type_value_formatter'][$type] ?? null)) {
            // TODO validate
            return $context['symfony']['type_extractor']->extractFromReturnType(new \ReflectionFunction($formatter));
        }

        return $type;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function accessor(string $type, string $accessor, array $context): string
    {
        if (null !== $formatter = ($context['symfony']['type_value_formatter'][$type] ?? null)) {
            return sprintf('$context[\'symfony\'][\'type_value_formatter\'][\'%s\'](%s, $context)', $type, $accessor);
        }

        return $accessor;
    }
}
