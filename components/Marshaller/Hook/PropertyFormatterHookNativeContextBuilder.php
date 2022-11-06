<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

use Symfony\Component\Marshaller\Attribute\Formatter;

final class PropertyFormatterHookNativeContextBuilder
{
    /**
     * @param array<string, mixed>
     *
     * @return array<string, mixed>
     */
    public function build(\ReflectionClass $class, string $format, array $context): array
    {
        if (!isset($context['hooks'])) {
            $context['hooks'] = [];
        }

        if (!isset($context['closures'])) {
            $context['closures'] = [];
        }

        $properties = $class->getProperties();
        foreach ($class->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Formatter::class !== $attribute->getName()) {
                    continue;
                }

                $closure = $attribute->newInstance()->closure;
                $this->validateClosure($closure, $property);

                $hookName = sprintf('%s::$%s', $class->getName(), $property->getName());

                $context['closures'][$hookName] = $closure;
                $context['hooks'][$hookName] = $this->createHook($closure, $hookName, $format);
            }
        }

        return $context;
    }

    private function createHook(\Closure $closure, string $hookName, string $format): callable
    {
        $valueGenerator = match ($format) {
            'json' => ValueTemplateGenerator::generateByType(...),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format)),
        };

        return static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($closure, $hookName, $valueGenerator, $format): string {
            $type = (new \ReflectionFunction($closure))->getReturnType();
            if ($type instanceof \ReflectionUnionType) {
                $type = $type->getTypes()[0];
            }

            $formattedValueAccessor = sprintf("\$context['closures']['%s'](%s->%s)", $hookName, $objectAccessor, $property->getName());

            $value = $valueGenerator($type, $formattedValueAccessor, $format, $context);
            if ('' === $value) {
                return $value;
            }

            return $context['propertyNameGenerator']($property, $context).$value;
        };
    }

    private function validateClosure(\Closure $closure, \ReflectionProperty $property): void
    {
        $valueParameter = (new \ReflectionFunction($closure))->getParameters()[0];

        if ((string) $valueParameter->getType() !== (string) $property->getType()) {
            throw new \InvalidArgumentException(sprintf(
                'Type of closure\'s argument of attribute "%s" must be the same as the "%s::$%s" property ("%s").',
                Formatter::class,
                $property->getDeclaringClass()->getName(),
                $property->getName(),
                (string) $property->getType(),
            ));
        }
    }
}
