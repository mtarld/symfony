<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\ValueTransformer;

use Symfony\Component\JsonStreamer\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type;

/**
 * TODO
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class ScalarToValueObjectValueTransformer implements ValueTransformerInterface
{
    public const NATIVE_TYPE_KEY = 'native_type';

    /**
     * @param array<class-string, ValueTransformerInterface> $valueTransformers
     */
    public function __construct(
        private array $valueTransformers,
    ) {
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        $nativeTypes = $options[self::NATIVE_TYPE_KEY] ?? throw new InvalidArgumentException('TODO');

        $hasValueWithoutTransformer = false;
        $exceptions = [];

        foreach (explode('|', $nativeTypes) as $nativeType) {
            if (null === $valueTransformer = $this->getValueTransformer($nativeType)) {
                $hasValueWithoutTransformer = true;

                continue;
            }

            try {
                return $valueTransformer->transform($value, $options);
            } catch (InvalidArgumentException $e) {
                $exceptions[] = $e;
            }
        }

        if ($exceptions && !$hasValueWithoutTransformer) {
            throw $exceptions[0];
        }

        return $value;
    }

    public static function getStreamValueType(): Type
    {
        return Type::union(Type::string(), Type::float(), Type::int(), Type::bool());
    }

    private function getValueTransformer(string $type): ?ValueTransformerInterface
    {
        foreach ($this->valueTransformers as $className => $valueTransformer) {
            if (is_a($type, $className, true)) {
                return $valueTransformer;
            }
        }

        return null;
    }
}
