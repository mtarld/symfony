<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Type;

use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class GenericType extends Type
{
    /**
     * @var list<Type>
     */
    private readonly array $genericTypes;

    public function __construct(
        private readonly BuiltinType|ObjectType $type,
        Type ...$genericTypes,
    ) {
        $this->genericTypes = $genericTypes;
    }

    public function getType(): BuiltinType|ObjectType
    {
        return $this->type;
    }

    /**
     * @return list<Type>
     */
    public function getGenericTypes(): array
    {
        return $this->genericTypes;
    }

    public function __toString(): string
    {
        $typeString = (string) $this->type;

        $genericTypesString = '';
        $glue = '';
        foreach ($this->genericTypes as $t) {
            $genericTypesString .= $glue.((string) $t);
            $glue = ',';
        }

        return $typeString.'<'.$genericTypesString.'>';
    }

    /**
     * Proxies all method calls to the original type.
     *
     * @param list<mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->type->{$method}(...$arguments);
    }
}
