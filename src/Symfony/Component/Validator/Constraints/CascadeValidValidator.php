<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\LogicException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class CascadeValidValidator extends ConstraintValidator
{
    private $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof CascadeValid) {
            throw new UnexpectedTypeException($constraint, CascadeValid::class);
        }

        $object = new \ReflectionClass($value);
        foreach ($object->getProperties() as $property) {
            if (null !== $property->getName()) {
                $value = $this->getPropertyAccessor()->getValue($value, 'name');
                dump($value);
            }
        }

        $this->context
            ->getValidator()
            ->inContext($this->context)
            ->validate($value, null, $this->context->getGroup());
    }

    private function getPropertyAccessor(): PropertyAccessor
    {
        if (null === $this->propertyAccessor) {
            if (!class_exists(PropertyAccess::class)) {
                throw new LogicException('Unable to use property path as the Symfony PropertyAccess component is not installed.');
            }
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
