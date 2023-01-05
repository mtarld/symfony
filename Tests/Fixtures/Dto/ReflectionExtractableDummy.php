<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

final class ReflectionExtractableDummy extends AbstractDummy
{
    public mixed $mixed;

    public int $int;

    public string $string;

    public float $float;

    public bool $bool;

    public array $array;

    public self $self;

    public parent $parent;

    public ClassicDummy $class;

    public string|int $union;

    public \Stringable&\Countable $intersection;

    public ?int $nullableBuiltin;
    public ?ClassicDummy $nullableClass;
    public string|int|null $nullableUnion;

    public $undefined;

    public function mixed(): mixed
    {
        return $this->mixed;
    }

    public function int(): int
    {
        return $this->int;
    }

    public function string(): string
    {
        return $this->string;
    }

    public function float(): float
    {
        return $this->float;
    }

    public function bool(): bool
    {
        return $this->bool;
    }

    public function array(): array
    {
        return $this->array;
    }

    public function self(): self
    {
        return $this->self;
    }

    public function parent(): parent
    {
        return $this->parent;
    }

    public function class(): ClassicDummy
    {
        return $this->class;
    }

    public function union(): string|int
    {
        return $this->union;
    }

    public function intersection(): \Stringable&\Countable
    {
        return $this->intersection;
    }

    public function nullableBuiltin(): ?int
    {
        return $this->nullableBuiltin;
    }

    public function nullableClass(): ?ClassicDummy
    {
        return $this->nullableClass;
    }

    public function nullableUnion(): string|int|null
    {
        return $this->nullableUnion;
    }

    public function void(): void
    {
    }

    public function never(): never
    {
        exit;
    }

    public function undefined()
    {
    }
}
