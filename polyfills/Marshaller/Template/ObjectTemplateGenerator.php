<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

use Symfony\Polyfill\Marshaller\Metadata\HookExtractor;
use Symfony\Polyfill\Marshaller\Metadata\Type;
use Symfony\Polyfill\Marshaller\Metadata\TypeFactory;

/**
 * @internal
 */
abstract class ObjectTemplateGenerator
{
    use PhpWriterTrait;
    use VariableNameScoperTrait;

    private readonly HookExtractor $hookExtractor;

    public function __construct(
        private readonly TemplateGenerator $templateGenerator,
    ) {
        $this->hookExtractor = new HookExtractor();
    }

    abstract protected function beforeProperties(): string;

    abstract protected function afterProperties(): string;

    abstract protected function propertySeparator(): string;

    abstract protected function propertyName(string $name): string;

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(Type $type, string $accessor, array $context): string
    {
        $class = new \ReflectionClass($type->className());

        $objectName = $this->scopeVariableName('object', $context);

        $template = $this->writeLine("$objectName = $accessor;", $context)
            .$this->fwrite(sprintf("'%s'", addslashes($this->beforeProperties())), $context);

        $properties = $class->getProperties();
        $propertySeparator = '';

        foreach ($properties as $i => $property) {
            if (null !== $hook = $this->hookExtractor->extractFromProperty($property, $context)) {
                $type = TypeFactory::createFromReflection($property->getType(), $property->getDeclaringClass());

                $hookContext = $context + [
                    'property_name_generator' => $this->generatePropertyName(...),
                    'property_value_generator' => $this->generatePropertyValue(...),
                    'property_type' => $type,
                    'property_separator' => $propertySeparator,
                ];

                $propertyAccessor = sprintf('%s->%s', $objectName, $property->getName());

                if (null !== $hookResult = $hook($property, $propertyAccessor, $this->templateGenerator->format(), $hookContext)) {
                    $template .= $hookResult;
                    $propertySeparator = $this->propertySeparator();

                    continue;
                }
            }

            $template .= $this->generatePropertyName($property, $propertySeparator, $context)
                .$this->generatePropertyValue($property, $objectName, $context);

            $propertySeparator = $this->propertySeparator();
        }

        $template .= $this->fwrite(sprintf("'%s'", addslashes($this->afterProperties())), $context);

        return $template;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function generatePropertyName(\ReflectionProperty $property, string $prefix, array $context): string
    {
        $name = $property->getName();
        foreach ($property->getAttributes() as $attribute) {
            if (\MarshalName::class !== $attribute->getName()) {
                continue;
            }

            $name = $attribute->newInstance()->name;

            break;
        }

        return $this->fwrite(sprintf("'%s%s'", $prefix, $this->propertyName($name)), $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function generatePropertyValue(\ReflectionProperty $property, string $accessor, array $context): string
    {
        $accessor = sprintf('%s->%s', $accessor, $property->getName());

        foreach ($property->getAttributes() as $attribute) {
            if (\MarshalFormatter::class === $attribute->getName()) {
                return $this->propertyFormatter($attribute->newInstance()->callable, $accessor, $context);
            }
        }

        $type = TypeFactory::createFromReflection($property->getType(), $property->getDeclaringClass());

        return $this->templateGenerator->generate($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function propertyFormatter(callable $formatter, string $accessor, array $context): string
    {
        $formatterReflection = (new \ReflectionFunction(\Closure::fromCallable($formatter)));
        $accessor = sprintf('%s(%s, $context)', $formatter, $accessor);
        $type = TypeFactory::createFromReflection($formatterReflection->getReturnType(), $formatterReflection->getClosureScopeClass());

        if (null !== $hook = $this->hookExtractor->extractFromFunction($formatterReflection, $context)) {
            if (null !== $hookResult = $hook($formatterReflection, $accessor, $this->templateGenerator->format(), $context)) {
                return $hookResult;
            }
        }

        return $this->templateGenerator->generate($type, $accessor, $context);
    }
}
