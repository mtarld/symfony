<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Type;

use Symfony\Component\SerDes\Exception\InvalidTypeException;

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
     * @template T of Type|UnionType
     *
     * @param T                             $type
     * @param array<string, Type|UnionType> $genericTypes
     *
     * @return T
     */
    public function replaceGenericTypes(Type|UnionType $type, array $genericTypes): Type|UnionType
    {
        if ($type instanceof UnionType) {
            return new UnionType(array_map(fn (Type $t): Type => $this->replaceGenericTypes($t, $genericTypes), $type->types));
        }

        if ([] !== $type->genericParameterTypes()) {
            $genericParameterTypes = array_map(fn (Type|UnionType $t): Type|UnionType => $this->replaceGenericTypes($t, $genericTypes), $type->genericParameterTypes());

            return new Type(
                name: $type->name(),
                isNullable: $type->isNullable(),
                className: $type->hasClass() ? $type->className() : null,
                isGeneric: true,
                genericParameterTypes: $genericParameterTypes,
            );
        }

        if (isset($genericTypes[(string) $type])) {
            return $genericTypes[(string) $type];
        }

        return $type;
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
