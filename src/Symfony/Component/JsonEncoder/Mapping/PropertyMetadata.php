<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Mapping;

use Symfony\Component\JsonEncoder\Exception\InvalidArgumentException;
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
     * @param list<\Closure> $formatters
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
        return new self($name, $this->type, $this->formatters);
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function withType(Type $type): self
    {
        return new self($this->name, $type, $this->formatters);
    }

    /**
     * @return list<\Closure>
     */
    public function getFormatters(): array
    {
        return $this->formatters;
    }

    /**
     * @param list<\Closure> $formatters
     */
    public function withFormatters(array $formatters): self
    {
        return new self($this->name, $this->type, $formatters);
    }

    public function withFormatter(\Closure $formatter): self
    {
        $formatters = $this->formatters;
        $formatters[] = $formatter;

        return $this->withFormatters($formatters);
    }

    private static function validateFormatters(self $metadata): void
    {
        foreach ($metadata->formatters as $formatter) {
            $reflection = new \ReflectionFunction($formatter);

            if ($reflection->getClosureScopeClass()?->hasMethod($reflection->getName())) {
                if (!$reflection->isStatic()) {
                    throw new InvalidArgumentException(sprintf('"%s"\'s property formatter must be a static method.', $metadata->name));
                }

                continue;
            }

            if ($reflection->isAnonymous()) {
                throw new InvalidArgumentException(sprintf('"%s"\'s property formatter must not be anonymous.', $metadata->name));
            }
        }
    }
}
