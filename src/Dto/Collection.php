<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Name;

final class Collection implements \IteratorAggregate
{
    #[Name('hydra:member')]
    public array $collection;

    #[Name('@type')]
    public string $type = 'hydra:Collection';

    #[Name('hydra:totalItems')]
    public int $totalItems = 0;

    public function __construct(...$collection)
    {
        $this->collection = $collection;
        $this->totalItems = \count($collection);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->collection);
    }
}
