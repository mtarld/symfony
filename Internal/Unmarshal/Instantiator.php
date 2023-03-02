<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Unmarshal;

use Symfony\Component\Marshaller\Exception\InvalidConstructorArgumentException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;
use Symfony\Component\Marshaller\Instantiator\InstantiatorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class Instantiator implements InstantiatorInterface
{
    /**
     * @var array<string, object>
     */
    private static array $cache = [];

    public function __invoke(\ReflectionClass $class, array $propertiesValues, array $context): object
    {
        if (isset(self::$cache[$className = $class->getName()])) {
            $object = clone self::$cache[$className];
        } else {
            if (null === $constructor = $class->getConstructor()) {
                $object = new ($class->getName())();
            } elseif (!$constructor->isPublic()) {
                $object = $class->newInstanceWithoutConstructor();
            } else {
                $parameters = [];
                $validContructor = true;

                foreach ($constructor->getParameters() as $parameter) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $parameters[] = $parameter->getDefaultValue();

                        continue;
                    }

                    if ($parameter->getType()?->allowsNull()) {
                        $parameters[] = null;

                        continue;
                    }

                    $exception = new InvalidConstructorArgumentException($parameter->getName(), $class->getName());
                    if (!($context['collect_errors'] ?? false)) {
                        throw $exception;
                    }

                    $context['collected_errors'][] = $exception;
                    $validContructor = false;
                }

                $object = ($validContructor ? $class->newInstanceArgs($parameters) : $class->newInstanceWithoutConstructor());
            }

            self::$cache[$className] = $object;
        }

        foreach ($propertiesValues as $property => $value) {
            try {
                $object->{$property} = $value();
            } catch (\TypeError $e) {
                $exception = new UnexpectedTypeException($e->getMessage());

                if (!($context['collect_errors'] ?? false)) {
                    throw $exception;
                }

                $context['collected_errors'][] = $exception;
            }
        }

        return $object;
    }
}
