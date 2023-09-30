<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Unmarshal\DataModel;

use Symfony\Component\JsonMarshaller\Type\Type;

/**
 * Represents an object in the data model graph representation.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class ObjectNode implements DataModelNodeInterface
{
    /**
     * @param array<string, array{name: string, value: DataModelNodeInterface, formatter: callable}> $properties
     */
    public function __construct(
        public Type $type,
        public array $properties,
        public bool $transformed,
        public bool $ghost = false,
    ) {
    }

    public static function ghost(Type $type): self
    {
        return new self($type, [], false, ghost: true);
    }

    public function identifier(): string
    {
        return (string) $this->type;
    }

    public function type(): Type
    {
        return $this->type;
    }

    public function isTransformed(): bool
    {
        return $this->transformed;
    }
}
