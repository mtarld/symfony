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
                $hookContext = $context + [
                    'propertyNameGenerator' => $this->generatePropertyName(...),
                    'propertyValueGenerator' => $this->generatePropertyValue(...),
                ];

                $template .= $hook($property, $objectName, $hookContext);

                continue;
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
         $content = '' === $prefix
             ? $this->propertyName($property->getName())
             : sprintf("'%s'.%s", $prefix, $this->propertyName($property->getName()))
         ;

         return $this->fwrite($content, $context);
     }

    /**
     * @param array<string, mixed> $context
     */
    private function generatePropertyValue(\ReflectionProperty $property, string $objectAccessor, array $context): string
    {
        $reflectionType = $property->getType();
        if (!$reflectionType instanceof \ReflectionNamedType) {
            throw new \LogicException('Not implemented yet (union/intersection).');
        }

        $type = TypeFactory::createFromReflection($reflectionType, $property->getDeclaringClass());

        $propertyAccessor = sprintf('%s->%s', $objectAccessor, $property->getName());

        $template = '';

        if ($type->isNullable()) {
            $template .= $this->writeLine("if (null === $propertyAccessor) {", $context);

            ++$context['indentation_level'];
            $template .= $this->fwrite("'null'", $context);

            --$context['indentation_level'];
            $template .= $this->writeLine('} else {', $context);

            ++$context['indentation_level'];
        }

        $template .= $this->templateGenerator->generate($type, $propertyAccessor, $context);

        if ($type->isNullable()) {
            --$context['indentation_level'];
            $template .= self::writeLine('}', $context);
        }

        return $template;
    }
}
