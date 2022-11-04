<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator\Json;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use Symfony\Component\Marshaller\Metadata\ValueMetadata;
use Symfony\Component\Marshaller\Template\Generator\YieldTrait;
use Symfony\Component\Marshaller\Template\Generator\ValueGeneratorInterface;
use Symfony\Component\Marshaller\Template\Generator\ValueGenerators;

final class JsonDictValueGenerator implements ValueGeneratorInterface
{
    use YieldTrait;

    public function __construct(
        private readonly ValueGenerators $valueGenerators,
    ) {
    }

    public function generate(ValueMetadata $value, Expr $accessor): array
    {
        $statements = [
            new Stmt\Expression(new Expr\Assign(new Expr\Variable('prefix'), new Scalar\String_('{'))),
        ];

        $statements[] = new Stmt\Foreach_($accessor, new Expr\Variable('value'), [
            'keyVar' => new Expr\Variable('key'),
            'stmts' => [
                $this->yield(new Expr\FuncCall(new Name('sprintf'), [
                    new Scalar\String_('%s%s:'),
                    new Expr\Variable('prefix'),
                    new Expr\FuncCall(new Name('json_encode'), [new Expr\Variable('key')]),
                ])),
                ...$this->valueGenerators->for($value->collectionValue())->generate($value->collectionValue(), new Expr\Variable('value')),
                new Stmt\Expression(new Expr\Assign(new Expr\Variable('prefix'), new Scalar\String_(','))),
            ],
        ]);

        $statements[] = new Stmt\Unset_([new Expr\Variable('prefix')]);
        $statements[] = $this->yield('}');

        if (!$value->isNullable()) {
            return $statements;
        }

        return [
            new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $accessor), [
                'stmts' => [
                    $this->yield('null'),
                ],
                'else' => new Stmt\Else_($statements),
            ])
        ];
    }

    public function canGenerate(ValueMetadata $value): bool
    {
        return $value->isArray() && $value->isDict();
    }
}
