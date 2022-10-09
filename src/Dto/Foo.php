<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Attribute\Groups;
use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Attribute\Warmable;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\DepthOption;
use Symfony\Component\Marshaller\Context\Option\GroupsOption;

#[Warmable(
    enforcedContexts: [
        new Context(new DepthOption(1, true), new GroupsOption('fooGroup')),
    ],
)]
final class Foo
{
    #[Name('@id')]
    #[Groups(['groupOne', 'groupTwo'])]
    #[Formatter(self::class, 'iri')]
    private string $iri = 'theIri';

    #[Groups('groupTwo')]
    public ?int $price = 12;

    /** @var array<string, string|null>|null */
    public ?array $dict;

    // /** @var list<string|null>|null */
    // public ?array $list = null;

    // #[Groups('groupOne')]
    // public ?Bar $obj = null;

    public function __construct()
    {
        $this->dict = ['foo' => 'bar', 'baz' => null];
        // $this->list = ['foo', null, 'baz'];
        // $this->list = null;
        // $this->obj = new Bar();
    }

    public static function iri(string $value, Context $context): string
    {
        return strtoupper($value);
    }
}
