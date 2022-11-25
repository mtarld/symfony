<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Name;

/**
 * @template T of object
 */
final class Collection
{
    #[Name('hydra:member')]
    /** @var list<T> */
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
}
