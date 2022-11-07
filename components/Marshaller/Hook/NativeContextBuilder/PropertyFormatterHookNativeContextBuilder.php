<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\NativeContextBuilder;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Context\MarshalNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Context\TemplateGenerationNativeContextBuilderInterface;
use Symfony\Component\Marshaller\Hook\ValueTemplateGenerator\ValueTemplateGenerator;
use Symfony\Component\Marshaller\Type\Type;
use Symfony\Component\Marshaller\Type\UnionTypeChecker;

final class PropertyFormatterHookNativeContextBuilder implements MarshalNativeContextBuilderInterface, TemplateGenerationNativeContextBuilderInterface
{
    public function forMarshal(\ReflectionClass $class, string $format, array $nativeContext): array
    {
        if (!isset($nativeContext['closures'])) {
            $nativeContext['closures'] = [];
        }

        foreach ($class->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Formatter::class !== $attribute->getName()) {
                    continue;
                }

                $hookName = sprintf('%s::$%s', $class->getName(), $property->getName());
                $nativeContext['closures'][$hookName] = $attribute->newInstance()->closure;
            }
        }

        return $nativeContext;
    }

    public function forTemplateGeneration(\ReflectionClass $class, string $format, array $nativeContext): array
    {
        if (!isset($nativeContext['hooks'])) {
            $nativeContext['hooks'] = [];
        }

        if (!isset($nativeContext['closures'])) {
            $nativeContext['closures'] = [];
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

                $nativeContext['closures'][$hookName] = $closure;
                $nativeContext['hooks'][$hookName] = $this->createHook($closure, $hookName, $format);
            }
        }

        return $nativeContext;
    }

    private function createHook(\Closure $closure, string $hookName, string $format): callable
    {
        $reflectionClosure = new \ReflectionFunction($closure);
        $returnType = $reflectionClosure->getReturnType();
        if ($returnType instanceof \ReflectionUnionType) {
            if (!UnionTypeChecker::isHomogenousKind($returnType->getTypes())) {
                throw new \RuntimeException(sprintf('Return type of "%s()" is not homogenous.', $reflectionClosure->getName()));
            }

            $returnType = $returnType->getTypes()[0];
        }

        return static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($returnType, $hookName): string {
            $formattedValueAccessor = sprintf("\$context['closures']['%s'](%s->%s)", $hookName, $objectAccessor, $property->getName());
            $value = ValueTemplateGenerator::generate(Type::createFromReflection($returnType), $formattedValueAccessor, $context);

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
            throw new \InvalidArgumentException(sprintf('Type of closure\'s argument of attribute "%s" must be the same as the "%s::$%s" property ("%s").', Formatter::class, $property->getDeclaringClass()->getName(), $property->getName(), (string) $property->getType()));
        }
    }
}
