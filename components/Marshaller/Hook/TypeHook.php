<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

/**
 * @internal
 */
final class TypeHook
{
    // TODO type_extractor from constructor instead

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(string $type, string $accessor, string $format, array $context): string
    {
        if (!isset($context['symfony']['type_extractor'])) {
            throw new \RuntimeException('Missing "$context[\'symfony\'][\'type_extractor\']".');
        }

        $context = $this->storeGenericTypes($type, $context);
        $accessor = $this->accessor($type, $accessor, $context);
        $accessorType = $this->type($type, $context);

        return $context['type_template_generator']($accessorType, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function storeGenericTypes(string $type, array $context): array
    {
        $results = [];
        if (!\preg_match('/^(?P<type>[^<]+)<(?P<diamond>.+)>$/', $type, $results)) {
            return $context;
        }

        $genericType = $results['type'];
        $genericParameters = [];
        $currentGenericParameter = '';
        $nestedLevel = 0;

        foreach (str_split(str_replace(' ', '', $results['diamond'])) as $char) {
            if (',' === $char && 0 === $nestedLevel) {
                $genericParameters[] = $currentGenericParameter;
                $currentGenericParameter = '';

                continue;
            }

            if ('<' === $char) {
                ++$nestedLevel;
            }

            if ('>' === $char) {
                --$nestedLevel;
            }

            $currentGenericParameter .= $char;
        }

        $genericParameters[] = $currentGenericParameter;

        if (0 !== $nestedLevel) {
            throw new \InvalidArgumentException(sprintf('Invalid "%s" type.', $type));
        }

        if (!class_exists($genericType)) {
            return $context;
        }

        $templates = $context['symfony']['type_extractor']->extractTemplateFromClass(new \ReflectionClass($genericType));

        if (\count($templates) !== \count($genericParameters)) {
            throw new \InvalidArgumentException(sprintf('Given %d generic parameters in "%s", but %d templates are defined in "%s".', \count($genericParameters), $type, \count($templates), $genericType));
        }

        foreach ($genericParameters as $i => $genericParameter) {
            $context['symfony']['generic_types'][$templates[$i]] = $genericParameter;
        }

        return $context;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function type(string $type, array $context): string
    {
        $formatter = $context['symfony']['type_formatter'][$type] ?? null;

        $type = null !== $formatter
            ? $context['symfony']['type_extractor']->extractFromReturnType(new \ReflectionFunction($formatter))
            : $type;

        if (isset($context['symfony']['generic_types'][$type])) {
            $type = $context['symfony']['generic_types'][$type];
        }

        return $type;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function accessor(string $type, string $accessor, array $context): string
    {
        if (null === $formatter = ($context['symfony']['type_formatter'][$type] ?? null)) {
            return $accessor;
        }

        $formatterReflection = new \ReflectionFunction($formatter);

        if (!$formatterReflection->getClosureScopeClass()?->hasMethod($formatterReflection->getName()) || !$formatterReflection->isStatic()) {
            throw new \InvalidArgumentException(sprintf('Type formatter "%s" must be a static method.', $type));
        }

        if (($returnType = $formatterReflection->getReturnType()) instanceof \ReflectionNamedType && ('void' === $returnType->getName() || 'never' === $returnType->getName())) {
            throw new \InvalidArgumentException(sprintf('Return type of type formatter "%s" must not be "void" nor "never".', $type));
        }

        if (2 !== \count($formatterReflection->getParameters())) {
            throw new \InvalidArgumentException(sprintf('Type formatter "%s" must have exactly two parameters.', $type));
        }

        if (null !== ($contextParameter = $formatterReflection->getParameters()[1] ?? null)) {
            $contextParameterType = $contextParameter->getType();

            if (!$contextParameterType instanceof \ReflectionNamedType || 'array' !== $contextParameterType->getName()) {
                throw new \InvalidArgumentException(sprintf('Second argument of type formatter "%s" must be an array.', $type));
            }
        }

        return sprintf('%s::%s(%s, $context)', $formatterReflection->getClosureScopeClass()->getName(), $formatterReflection->getName(), $accessor);
    }
}
