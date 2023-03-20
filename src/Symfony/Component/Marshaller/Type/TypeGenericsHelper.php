<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Type;

use Symfony\Component\Marshaller\Exception\InvalidTypeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TypeGenericsHelper
{
    /**
     * @var array<string, array{genericType: string, genericParameters: list<string>}>
     */
    private static array $genericsCache = [];

    /**
     * @param array<string, string> $genericTypes
     */
    public function replaceGenericTypes(string $type, array $genericTypes): string
    {
        $types = $this->explodeUnion($type);

        if (\count($types) > 1) {
            return implode('|', array_map(fn (string $t): string => $this->replaceGenericTypes($t, $genericTypes), $types));
        }

        [$type, $genericParameterTypes] = $this->explodeGenerics($type);

        if ([] !== $genericParameterTypes) {
            return sprintf(
                '%s<%s>',
                $this->replaceGenericTypes($type, $genericTypes),
                implode(', ', array_map(fn (string $t): string => $this->replaceGenericTypes($t, $genericTypes), $genericParameterTypes)),
            );
        }

        return str_replace(array_keys($genericTypes), array_values($genericTypes), $type);
    }

    /**
     * @return array{genericType: string, genericParameters: list<string>}
     */
    public function extractGenerics(string $type): array
    {
        if (isset(self::$genericsCache[$type])) {
            return self::$genericsCache[$type];
        }

        $results = [];
        if (!preg_match('/^(?P<type>[^<]+)<(?P<diamond>.+)>$/', $type, $results)) {
            return self::$genericsCache[$type] = [
                'genericType' => $type,
                'genericParameters' => [],
            ];
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

        if (0 !== $nestedLevel) {
            throw new InvalidTypeException($type);
        }

        $genericParameters[] = $currentGenericParameter;

        return self::$genericsCache[$type] = [
            'genericType' => $genericType,
            'genericParameters' => $genericParameters,
        ];
    }

    /**
     * @return list<string>
     */
    private function explodeUnion(string $type): array
    {
        $currentTypeString = '';
        $typeStrings = [];
        $nestedLevel = 0;

        foreach (str_split(str_replace(' ', '', $type)) as $char) {
            if ('<' === $char) {
                ++$nestedLevel;
            }

            if ('>' === $char) {
                --$nestedLevel;
            }

            if ('|' === $char && 0 === $nestedLevel) {
                $typeStrings[] = $currentTypeString;
                $currentTypeString = '';

                continue;
            }

            $currentTypeString .= $char;
        }

        $typeStrings[] = $currentTypeString;

        return $typeStrings;
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function explodeGenerics(string $type): array
    {
        $matches = [];
        if (!preg_match('/^(?P<type>[^<]+)<(?P<diamond>.+)>$/', $type, $matches)) {
            return [$type, []];
        }

        $genericType = $matches['type'];
        $genericParameterTypes = [];

        $currentGenericParameterType = '';
        $nestedLevel = 0;

        foreach (str_split(str_replace(' ', '', $matches['diamond'])) as $char) {
            if (',' === $char && 0 === $nestedLevel) {
                $genericParameterTypes[] = $currentGenericParameterType;
                $currentGenericParameterType = '';

                continue;
            }

            if ('<' === $char) {
                ++$nestedLevel;
            }

            if ('>' === $char) {
                --$nestedLevel;
            }

            $currentGenericParameterType .= $char;
        }

        $genericParameterTypes[] = $currentGenericParameterType;

        return [$genericType, $genericParameterTypes];
    }
}
