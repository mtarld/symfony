<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

/**
 * @internal
 */
final class PropertyHook
{
    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(\ReflectionProperty $property, string $accessor, string $format, array $context): string
    {
        if (!$property->isPublic()) {
            throw new \RuntimeException(sprintf('"%s::$%s" must be public', $property->getDeclaringClass()->getName(), $property->getName()));
        }

        $symfonyContext = $context['symfony'] ?? [];

        $identifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());

        $type = $context['property_type'];
        if (isset($symfonyContext['property_type_extractor'])) {
            $type = $context['symfony']['property_type_extractor']($property);
        }

        $name = sprintf("'%s'", $property->getName());
        if (isset($symfonyContext['property_name_formatter'][$identifier])) {
            $name = sprintf('$context[\'symfony\'][\'property_name_formatter\'][\'%s\'](%s, $context)', $identifier, $name);
        }

        $nameTemplate = $context['name_template_generator']($name, $context);

        if (null !== $formatter = ($symfonyContext['property_value_formatters'][$identifier] ?? null)) {
            $accessor = sprintf('$context[\'symfony\'][\'property_value_formatters\'][\'%s\'](%s, $context)', $identifier, $accessor);
            // TODO throw if not set
            $type = $symfonyContext['formatter_type_extractor'](new \ReflectionFunction(\Closure::fromCallable($formatter)));
            // $type ??= TypeFactory::createFromReflection($formatterReflection->getReturnType(), $formatterReflection->getClosureScopeClass());
        }

        $valueTemplate = $context['value_template_generator']($type, $accessor, $context);

        return $nameTemplate.$valueTemplate;
    }
}
