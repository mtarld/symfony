<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\Option;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class TypeFormatterOption
{
    /**
     * @var array<string, \Closure>
     */
    public readonly array $formatters;

    /**
     * @param array<string, callable> $typeFormatters
     */
    public function __construct(array $typeFormatters)
    {
        $formatters = [];

        foreach ($typeFormatters as $typeName => $formatter) {
            if (!\is_callable($formatter)) {
                throw new \InvalidArgumentException(sprintf('Formatter "%s" of attribute "%s" is an invalid callable.', $typeName, self::class));
            }

            $formatters[$typeName] = \Closure::fromCallable($formatter);
        }

        $this->formatters = $formatters;
    }
}
