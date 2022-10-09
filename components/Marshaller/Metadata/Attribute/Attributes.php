<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Metadata\Attribute;

final class Attributes implements \IteratorAggregate
{
    /**
     * @template T of object
     *
     * @var array<class-string<T>, T>
     */
    private readonly array $attributeMap;

    public function __construct(array $attributes)
    {
        $map = [];
        foreach ($attributes as $attribute) {
            $map[get_class($attribute)] = $attribute;
        }

        $this->attributeMap = $map;
    }

    /**
     * @param class-string $attributeClass
     */
    public function has(string $attributeClass): bool
    {
        return isset($this->attributeMap[$attributeClass]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>
     *
     * @return T
     *
     * @throws \OutOfBoundsException
     */
    public function get(string $attributeClass): object
    {
        if (!$this->has($attributeClass)) {
            throw new \OutOfBoundsException('TODO');
        }

        return $this->attributeMap[$attributeClass];
    }

    /**
     * @return \Traversable<object>
     */
    public function getIterator(): \Traversable
    {
        yield from array_values($this->attributeMap);
    }
}
