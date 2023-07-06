<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Instantiator;

use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Type\TypeGenericsHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class EagerInstantiator implements InstantiatorInterface
{
    /**
     * @var array{reflection: array<class-string, \ReflectionClass<object>>, class_has_property: array<class-string, bool>}
     */
    private static array $cache = [
        'reflection' => [],
        'has_property' => [],
    ];

    private readonly TypeGenericsHelper $typeGenericsHelper;

    public function __construct()
    {
        $this->typeGenericsHelper = new TypeGenericsHelper();
    }

    public function instantiate(string $className, array $properties): object
    {
        $object = new $className();
        $reflection = self::$cache['reflection'][$className] ??= new \ReflectionClass($className);

        foreach ($properties as $name => $value) {
            if (!(self::$cache['has_property'][$identifier = $className.$name] ??= $reflection->hasProperty($name))) {
                continue;
            }

            try {
                $object->{$name} = $value();
            } catch (\TypeError|UnexpectedValueException $e) {
                throw new UnexpectedValueException($e->getMessage(), previous: $e);
            }
        }

        return $object;
    }
}
