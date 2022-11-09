<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Attribute\Groups;
use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Attribute\Warmable;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\DepthOption;

#[Warmable]
final class Foo
{
    #[Name('@id')]
    #[Formatter([self::class, 'iri'])]
    public string $iri = 'theIri';

    /** @var array<string, string|null>|null */
    public ?array $dict = ['a' => null, 'b' => 'c'];

//     /** @var array<string, string|null>|null */
//     #[Hook('my_property_accessor')]
//     public array $collection; // component
//
//     /** @var array<string, string|null>|null */
//     private array $collection;
//
//
    // // [
    // // 'cache_path' => '' ?? sys_tmp_dir(),
    // // 'hooks' => {
    // // 'array' => fn
    // // 'Foo::$collection' => fn,
    // // 'max_depth' => 512,
    // // 'reject_circular_reference' => true,
    // // }
    // // ]
//
//
//     marshal($data, $resource, $format, $context = []): void;
//
//     json_marshal($data, $resource, $context): void;
//     json_generate(\Reflector $type, array $context): string;
//
//     xml_marshal($data, $resource, $context): void;
//     xml_generate(\Reflector $type, array $context): string;
//
//     compute_metadata(\Reflector $type, array $context): string;
//
//     my_hook(\Reflector $type, array $context): string;
//
//     #[CustomGenerator('phpstan_formatter')] // polyfill
//     public array $collection;
//
//     /** @return array<Stmt> */
//     phpstan_formatter(\ReflectionAttribte $attribute, array $context): array {
//         return [new Expr\...];
//     }
//
//     /** @var list<string|null>|null */
//     public ?array $list = null;
//
//     // #[Groups('groupOne')]
//     // public ?Bar $obj = null;
//
//     public function __construct()
//     {
//         // $this->dict = ['foo' => 'bar', 'baz' => null];
//         $this->list = ['foo', null, 'baz'];
//         // $this->list = null;
//         // $this->obj = new Bar();
//     }
//
    public static function iri(string $value): string
    {
        return sprintf('/api/%s', $value);
    }
}
