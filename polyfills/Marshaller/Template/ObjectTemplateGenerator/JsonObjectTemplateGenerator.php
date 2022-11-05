<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template\ObjectTemplateGenerator;

use Symfony\Polyfill\Marshaller\Metadata\PropertyHookExtractor;
use Symfony\Polyfill\Marshaller\Metadata\PropertyKindExtractor;
use Symfony\Polyfill\Marshaller\Template\ObjectTemplateGeneratorInterface;

final class JsonObjectTemplateGenerator implements ObjectTemplateGeneratorInterface
{
    private readonly PropertyKindExtractor $propertyKindExtractor;
    private readonly PropertyHookExtractor $propertyHookExtractor;

    public function __construct()
    {
        $this->propertyKindExtractor = new PropertyKindExtractor();
        $this->propertyHookExtractor = new PropertyHookExtractor();
    }

    public function generate(\ReflectionClass $class, string $accessor, array $context): string
    {
        $template = '';
        $context['classes'][] = $class->getName();

        $prefix = '{';
        foreach ($class->getProperties() as $property) {
            if (null !== $hook = $this->propertyHookExtractor->extract($property, $context)) {
                $template .= $hook($property, $accessor, $context);

                continue;
            }

            $propertyValue = $this->generatePropertyValue($property, $accessor, $context);

            if (null !== $propertyValue) {
                $propertyName = json_encode($property->getName());
                $template .= $this->write("'$prefix$propertyName:'", $context['indentation_level']).$propertyValue;
            }

            $prefix = ',';
        }

        $template .= $this->write("'}'", $context['indentation_level']);

        return $template;
    }

    private function generatePropertyValue(\ReflectionProperty $property, string $objectAccessor, array $context): ?string
    {
        $propertyKind = $this->propertyKindExtractor->extract($property);
        $propertyAccessor = sprintf('%s->%s', $objectAccessor, $property->getName());

        if (PropertyKindExtractor::KIND_SCALAR === $propertyKind) {
            return $this->write("json_encode($propertyAccessor)", $context['indentation_level']);
        }

        if (PropertyKindExtractor::KIND_OBJECT === $propertyKind) {
            ++$context['depth'];

            if ($context['depth'] > $context['max_depth']) {
                return null;
            }

            $className = $property->getType()->getName();
            if (isset($context['classes'][$className]) && $context['reject_circular_reference']) {
                throw new \RuntimeException(sprintf('Circular reference on "%s" detected.', $className));
            }

            return $this->generate(new \ReflectionClass($className), $propertyAccessor, $context);
        }

        throw new \LogicException(sprintf('Unexpected "%s" property kind', $propertyKind));
    }

    private function write(string $content, int $indentationLevel): string
    {
        return sprintf("%sfwrite(\$resource, $content);%s", str_repeat(' ', 4 * $indentationLevel), PHP_EOL);
    }
}
