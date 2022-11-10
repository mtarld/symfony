<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

use Symfony\Polyfill\Marshaller\Metadata\HookExtractor;
use Symfony\Polyfill\Marshaller\Metadata\Type;

/**
 * @internal
 */
abstract class ObjectTemplateGenerator
{
    use PhpWriterTrait;
    use VariableNameScoperTrait;

    private readonly HookExtractor $hookExtractor;

    public function __construct(
        private readonly TemplateGeneratorInterface $templateGenerator,
    ) {
        $this->hookExtractor = new HookExtractor();
    }

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function generateBeforeProperties(array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function generateAfterProperties(array $context): string;

    abstract protected function getBeforePropertyString(bool $isFirst, bool $isLast): string;

    abstract protected function getAfterPropertyString(bool $isFirst, bool $isLast): string;

    abstract protected function getPropertyNameString(string $propertyName): string;

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(Type $type, string $accessor, array $context): string
    {
        $className = $type->className();
        $class = new \ReflectionClass($className);

        if (isset($context['classes'][$className]) && $context['reject_circular_reference']) {
            throw new \RuntimeException(sprintf('Circular reference on "%s" detected.', $className));
        }

        $context['classes'][$className] = true;

        if ($context['depth'] > $context['max_depth']) {
            return '';
        }

        $objectName = $this->scopeVariableName('object', $context);

        $template = $this->writeLine("$objectName = $accessor;", $context);
        $template .= $this->generateBeforeProperties($context);

        $properties = $class->getProperties();
        $lastIndex = \count($properties) - 1;

        foreach ($properties as $i => $property) {
            if (null !== $hook = $this->hookExtractor->extractFromProperty($property, $context)) {
                $hookContext = $context + [
                    'propertyNameGenerator' => $this->generatePropertyName(...),
                    'propertyValueGenerator' => $this->generatePropertyValue(...),
                    'fwrite' => $this->fwrite(...),
                    'writeLine' => $this->writeLine(...),
                ];

                $template .= $hook($property, $objectName, $hookContext);

                continue;
            }

            if (null === $propertyValue = $this->generatePropertyValue($property, $objectName, $context)) {
                continue;
            }

            $isFirst = 0 === $i;
            $isLast = $lastIndex === $i;

            $template .= $this->generatePropertyName($property, $this->getBeforePropertyString($isFirst, $isLast), $context);
            $template .= $propertyValue;

            if ('' !== $afterPropertyString = $this->getAfterPropertyString($isFirst, $isLast)) {
                $template .= $this->fwrite($afterPropertyString, $context);
            }
        }

        $template .= $this->generateAfterProperties($context);

        return $template;
    }

     /**
      * @param array<string, mixed> $context
      */
     private function generatePropertyName(\ReflectionProperty $property, string $prefix, array $context): string
     {
         return $this->fwrite(sprintf("'%s%s'", $prefix, $this->getPropertyNameString($property->getName())), $context);
     }

    /**
     * @param array<string, mixed> $context
     */
    private function generatePropertyValue(\ReflectionProperty $property, string $objectAccessor, array $context): ?string
    {
        $reflectionType = $property->getType();
        if (!$reflectionType instanceof \ReflectionNamedType) {
            throw new \LogicException('Not implemented yet (union/intersection).');
        }

        $type = Type::fromReflection($reflectionType, $property->getDeclaringClass());

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

        if ($type->isScalar()) {
            $template .= $this->templateGenerator->generateScalar($type, $propertyAccessor, $context + ['enclosed' => false]);
        } elseif ($type->isObject()) {
            ++$context['depth'];
            $template .= $this->generate($type, $propertyAccessor, $context);
        } else {
            throw new \LogicException(sprintf('Unexpected "%s" property kind', $propertyKind));
        }

        if ($type->isNullable()) {
            --$context['indentation_level'];
            $template .= self::writeLine('}', $context);
        }

        return $template;
    }
}
