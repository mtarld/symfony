<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Internal\Serialize\TemplateGenerator;

use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;
use Symfony\Component\Serializer\Type\TypeSorter;

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
        $reflectionTypeExtractor = new ReflectionTypeExtractor();
        $typeSorter = new TypeSorter();

        return match ($format) {
            'json' => new JsonTemplateGenerator($reflectionTypeExtractor, $typeSorter),
            'csv' => new CsvTemplateGenerator($reflectionTypeExtractor, $typeSorter),
            default => throw new UnsupportedException(sprintf('"%s" format is not supported.', $format)),
        };
    }
}
