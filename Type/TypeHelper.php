<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Type;

final class TypeHelper
{
    /**
     * @return list<class-string>
     */
    public function extractClassNames(string $type): array
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

        if (\count($typeStrings) > 1) {
            return array_unique(array_merge(...array_map($this->extractClassNames(...), $typeStrings)));
        }

        if (class_exists($type) || interface_exists($type)) {
            return [$type];
        }

        $matches = [];
        if (preg_match('/^(?P<type>[^<]+)<(?P<diamond>.+)>$/', $type, $matches)) {
            $classNames = [];
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
            $type = $genericType;

            return array_unique(array_merge($this->extractClassNames($type), ...array_map($this->extractClassNames(...), $genericParameterTypes)));
        }

        return [];
    }
}
