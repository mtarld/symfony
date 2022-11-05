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
    public function build(\ReflectionClass $class, array $context): array
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
                $context['hooks'][$hookName] = $this->createHook($closure, $hookName);
            }
        }

        return $context;
    }

    // TODO must have declination depending on format
    private function createHook(\Closure $closure, string $hookName): callable
    {
        return static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($closure, $hookName): string {
            // TODO extract?
            $name = $context['propertyNameGenerator']($property, $context);
            $closureReflection = new \ReflectionFunction($closure);

            $type = $closureReflection->getReturnType();
            if ($type instanceof \ReflectionUnionType) {
                $type = $type->getTypes()[0];
            }

            $kind = self::extractKind($type);

            $propertyAccessor = sprintf('%s->%s', $objectAccessor, $property->getName());
            $formattedValue = sprintf("\$context['closures']['$hookName']($propertyAccessor)");

            if ('scalar' === $kind) {
                return $name.$context['fwrite']("json_encode($formattedValue)", $context);
            }

            if ('object' === $kind) {
                $foo = marshal_generate(new \ReflectionClass($type->getName()), 'json', $context);
                dd($foo);
            }

            throw new \RuntimeException('Not implemented yet.');
        };
    }

    private static function extractKind(\ReflectionNamedType $type): ?string
    {
        if (in_array($type->getName(), ['int', 'float', 'string', 'bool'], true)) {
            return 'scalar';
        }

        if (!$type->isBuiltin()) {
            return 'object';
        }

        throw new \LogicException(sprintf('Cannot handle return type "%s" of "%s()" closure.', $type, $reflection->getName()));
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
