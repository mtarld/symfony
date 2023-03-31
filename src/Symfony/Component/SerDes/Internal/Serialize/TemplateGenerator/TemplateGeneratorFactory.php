<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Internal\Serialize\TemplateGenerator;

use Symfony\Component\SerDes\Exception\UnsupportedFormatException;
use Symfony\Component\SerDes\Internal\Serialize\TypeSorter;
use Symfony\Component\SerDes\Type\ReflectionTypeExtractor;

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
        return new JsonTemplateGenerator(
            reflectionTypeExtractor: new ReflectionTypeExtractor(),
            typeSorter: new TypeSorter(),
        );
    }
}
