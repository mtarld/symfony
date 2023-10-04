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

use Symfony\Component\TypeInfo\BuiltinType as BuiltinTypeEnum;
use Symfony\Component\TypeInfo\Exception\InvalidArgumentException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class BackedEnumType extends EnumType
{
    /**
     * @param class-string $className
     */
    public function __construct(
        string $className,
        private readonly BuiltinType $backingType,
    ) {
        if (!is_subclass_of($className, \BackedEnum::class)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid backed enum.', $className));
        }

        if (!\in_array($backingType->getBuiltinType(), [BuiltinTypeEnum::INT, BuiltinTypeEnum::STRING], true)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid enum backing type.', $backingType));
        }

        parent::__construct($className);
    }

    public function getBackingType(): BuiltinType
    {
        return $this->backingType;
    }
}
