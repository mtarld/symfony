<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Mapping;

use Symfony\Component\Encoder\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type;

/**
 * Holds encoding/decoding metadata about a given property.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class PropertyMetadata
{
    /**
     * @param list<callable> $formatters
     */
    public function __construct(
        private string $name,
        private Type $type,
        private array $formatters = [],
    ) {
        self::validateFormatters($this);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function withType(Type $type): self
    {
        $clone = clone $this;
        $clone->type = $type;

        return $clone;
    }

    /**
     * @return list<callable>
     */
    public function getFormatters(): array
    {
        return $this->formatters;
    }

    /**
     * @param list<callable> $formatters
     */
    public function withFormatters(array $formatters): self
    {
        $clone = clone $this;
        $clone->formatters = $formatters;

        self::validateFormatters($clone);

        return $clone;
    }

    public function withFormatter(callable $formatter): self
    {
        $formatters = $this->formatters;
        $formatters[] = $formatter;

        return $this->withFormatters($formatters);
    }

    private static function validateFormatters(self $metadata): void
    {
        foreach ($metadata->formatters as $formatter) {
            $reflection = new \ReflectionFunction(\Closure::fromCallable($formatter));

            if ($reflection->getClosureScopeClass()?->hasMethod($reflection->getName())) {
                if (!$reflection->isStatic()) {
                    throw new InvalidArgumentException(sprintf('"%s"\'s property formatter must be a static method.', $metadata->name));
                }
            } else {
                if ($reflection->isAnonymous()) {
                    throw new InvalidArgumentException(sprintf('"%s"\'s property formatter must not be anonymous.', $metadata->name));
                }
            }
        }
    }
}
