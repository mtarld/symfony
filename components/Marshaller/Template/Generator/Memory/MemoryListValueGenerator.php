<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator\Memory;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Metadata\ValueMetadata;
use Symfony\Component\Marshaller\Template\Generator\YieldTrait;
use Symfony\Component\Marshaller\Template\Generator\ValueGeneratorInterface;
use Symfony\Component\Marshaller\Template\Generator\ValueGenerators;

final class MemoryListValueGenerator implements ValueGeneratorInterface
{
    use YieldTrait;

    public function __construct(
        private readonly ValueGenerators $valueGenerators,
    ) {
    }

    public function generate(ValueMetadata $value, Expr $accessor): array
    {
        $valueStatements = $this->valueGenerators->for($value->collectionValue())->generate($value->collectionValue(), new Expr\Variable('item'));
        $valueStatement = $valueStatements[0];

        if (\count($valueStatements) > 1 || !$valueStatements[0] instanceof Expr) {
            $valueStatement = $this->encloseStatements($valueStatements, 'item');
        }

        $statements = [
            new Stmt\Foreach_($accessor, new Expr\Variable('item'), [
                'stmts' => [
                    $this->yield($valueStatement),
                ],
            ]),
        ];

        if (!$value->isNullable()) {
            return $statements;
        }

        return [
            new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $accessor), [
                'stmts' => [
                    $this->yield('null'),
                ],
                'else' => new Stmt\Else_($statements),
            ]),
        ];
    }

    public function canGenerate(ValueMetadata $value): bool
    {
        return $value->isArray() && $value->isList();
    }

    /**
     * @param array<Stmt> $valueStatements
     */
    private function encloseStatements(array $valueStatements, string $itemName): Expr\FuncCall
    {
        $closure = new Expr\Closure([
            'static' => true,
            'uses' => [
                new Param(new Expr\Variable($itemName)),
                new Param(new Expr\Variable('context')),
            ],
            'stmts' => $valueStatements,
        ]);

        return new Expr\FuncCall($closure);
    }
}
