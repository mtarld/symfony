<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Fixtures;

final class PhpstanExtractableDummy extends AbstractDummy
{
    /** @var mixed */
    public $mixed;

    /** @var bool */
    public $bool;

    /** @var bool */
    public $boolean;

    /** @var true */
    public $true;

    /** @var false */
    public $false;

    /** @var int */
    public $int;

    /** @var int */
    public $integer;

    /** @var float */
    public $float;

    /** @var string */
    public $string;

    /** @var resource */
    public $resource;

    /** @var object */
    public $object;

    /** @var callable */
    public $callable;

    /** @var array */
    public $array;

    /** @var list */
    public $list;

    /** @var iterable */
    public $iterable;

    /** @var non-empty-array */
    public $nonEmptyArray;

    /** @var non-empty-list */
    public $nonEmptyList;

    /** @var null */
    public $null;

    /** @var self */
    public $self;

    /** @var static */
    public $static;

    /** @var parent */
    public $parent;

    /** @var scoped */
    public $scoped;

    /** @var int|string */
    public $union;

    /** @var ?int */
    public $nullable;

    /** @var int|string|null */
    public $nullableUnion;

    /** @var list<string> */
    public $genericList;

    /** @var array<string> */
    public $genericArrayList;

    /** @var array<string, string> */
    public $genericDict;

    /** @var string[] */
    public $squareBracketList;

    /** @var array{foo: int, bar: string} */
    public $bracketList;

    /** @var array{} */
    public $emptyBracketList;

    /** @var int&string */
    public $intersection;

    /** @var ArrayIterator<T> */
    public $nonArrayGeneric;

    public $undefined;

    /** @return mixed */
    public function mixed()
    {
        return $this->mixed;
    }

    /** @return bool */
    public function bool()
    {
        return $this->bool;
    }

    /** @return bool */
    public function boolean()
    {
        return $this->boolean;
    }

    /** @return true */
    public function true()
    {
        return $this->true;
    }

    /** @return false */
    public function false()
    {
        return $this->false;
    }

    /** @return int */
    public function int()
    {
        return $this->int;
    }

    /** @return int */
    public function integer()
    {
        return $this->integer;
    }

    /** @return float */
    public function float()
    {
        return $this->float;
    }

    /** @return string */
    public function string()
    {
        return $this->string;
    }

    /** @return resource */
    public function resource()
    {
        return $this->resource;
    }

    /** @return object */
    public function object()
    {
        return $this->object;
    }

    /** @return callable */
    public function callable()
    {
        return $this->callable;
    }

    /** @return array */
    public function array()
    {
        return $this->array;
    }

    /** @return list */
    public function list()
    {
        return $this->list;
    }

    /** @return iterable */
    public function iterable()
    {
        return $this->iterable;
    }

    /** @return non-empty-array */
    public function nonEmptyArray()
    {
        return $this->nonEmptyArray;
    }

    /** @return non-empty-list */
    public function nonEmptyList()
    {
        return $this->nonEmptyList;
    }

    /** @return null */
    public function null()
    {
        return $this->null;
    }

    /** @return self */
    public function self()
    {
        return $this->self;
    }

    /** @return static */
    public function static()
    {
        return $this->static;
    }

    /** @return parent */
    public function parent()
    {
        return $this->parent;
    }

    /** @return scoped */
    public function scoped()
    {
        return $this->scoped;
    }

    /** @return int|string */
    public function union()
    {
        return $this->union;
    }

    /** @return ?int */
    public function nullable()
    {
        return $this->nullable;
    }

    /** @return int|string|null */
    public function nullableUnion()
    {
        return $this->nullableUnion;
    }

    /** @return list<string> */
    public function genericList()
    {
        return $this->genericList;
    }

    /** @return array<string> */
    public function genericArrayList()
    {
        return $this->genericArrayList;
    }

    /** @return array<string, string> */
    public function genericDict()
    {
        return $this->genericDict;
    }

    /** @return string[] */
    public function squareBracketList()
    {
        return $this->squareBracketList;
    }

    /** @return array{foo: int, bar: string} */
    public function bracketList()
    {
        return $this->bracketList;
    }

    /** @return array{} */
    public function emptyBracketList()
    {
        return $this->emptyBracketList;
    }

    /** @return int&string */
    public function intersection()
    {
        return $this->intersection;
    }

    /** @return ArrayIterator<T> */
    public function nonArrayGeneric()
    {
        return $this->nonArrayGeneric;
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
        return $this->undefined;
    }
}
