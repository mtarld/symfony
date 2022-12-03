<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

use Symfony\Component\Marshaller\Type\TypeExtractorInterface;

/**
 * @internal
 */
final class ObjectHook
{
    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array{type: string, accessor: string, context: array<string, mixed>}
     */
    public function __invoke(string $type, string $accessor, array $context): array
    {
        return [
            'type' => $type,
            'accessor' => $accessor,
            'context' => $this->addGenericParameterTypes($type, $context),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addGenericParameterTypes(string $type, array $context): array
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

        $templates = $this->typeExtractor->extractTemplateFromClass(new \ReflectionClass($genericType));

        if (\count($templates) !== \count($genericParameters)) {
            throw new \InvalidArgumentException(sprintf('Given %d generic parameters in "%s", but %d templates are defined in "%s".', \count($genericParameters), $type, \count($templates), $genericType));
        }

        foreach ($genericParameters as $i => $genericParameter) {
            $context['symfony']['generic_parameter_types'][$genericType][$templates[$i]] = $genericParameter;
        }

        return $context;
    }
}
