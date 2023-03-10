<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Marshal;

use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Internal\Marshal\Json\JsonSyntax;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TemplateGeneratorFactory
{
    private function __construct()
    {
    }

    public static function create(string $format): TemplateGenerator
    {
        return match ($format) {
            'json' => self::json(),
            default => throw new UnsupportedFormatException($format),
        };
    }

    private static function json(): TemplateGenerator
    {
        return new TemplateGenerator(
            reflectionTypeExtractor: new ReflectionTypeExtractor(),
            typeSorter: new TypeSorter(),
            syntax: new JsonSyntax(),
        );
    }
}
