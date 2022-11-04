<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template\Json;

final class ObjectGenerator
{
    private readonly ScalarGenerator $scalarGenerator;

    public function __construct(
    ) {
        $this->scalarGenerator = new ScalarGenerator();
    }

    public function generate(\ReflectionClass $reflectionClass, string $accessor, array $context = []): string
    {
        $context['classes'][] = $reflectionClass->getName();

        $result = '';

        $prefix = '{';
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            // TODO hook to change name
            $propertyName = json_encode($reflectionProperty->getName());

            $result .= $this->writeLine("fwrite(\$resource, '$prefix$propertyName:');", $context['indentation_level']);
            $result .= $this->generatePropertyValue($reflectionProperty, $accessor, $context);
            $prefix = ',';
        }

        $result .= $this->writeLine("fwrite(\$resource, '}');", $context['indentation_level']);

        return $result;
    }

    private function generatePropertyValue(\ReflectionProperty $reflectionProperty, string $objectAccessor, array $context): string
    {
        $accessor = sprintf('%s->%s', $objectAccessor, $reflectionProperty->getName());

        // TODO handle union types
        /** @var \ReflectionNamedType $type */
        $type = $reflectionProperty->getType();
        if (in_array($type->getName(), ['int', 'float', 'string'], true)) {
            return $this->scalarGenerator->generate($reflectionProperty, sprintf('%s->%s', $objectAccessor, $reflectionProperty->getName()), $context);
        }

        // TODO better way to handle classes?
        if (!$type->isBuiltin()) {
            ++$context['depth'];
            if ($context['depth'] >= $context['max_depth']) {
                return '';
            }

            $className = $type->getName();
            if (isset($context['classes'][$className]) && $context['reject_circular_reference']) {
                throw new \RuntimeException('circular');
            }

            // TODO hook to change value

            return $this->generate(new \ReflectionClass($type->getName()), $accessor, $context);
        }

        throw new \RuntimeException(sprintf('Unhandled "%s" type, you may add a custom hook to handle it.', $type));
    }

    private function writeLine(string $php, int $indentationLevel): string
    {
        return sprintf('%s%s%s', str_repeat(' ', 4 * $indentationLevel), $php, PHP_EOL);
    }
}
