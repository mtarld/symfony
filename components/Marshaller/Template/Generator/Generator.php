<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator;

use PhpParser\Comment;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Metadata\ValueMetadata;
use Symfony\Component\Marshaller\Output\OutputInterface;

final class Generator
{
    use OutputWriterTrait;

    public function __construct(
        private readonly StructureGeneratorInterface $structureGenerator,
        private readonly ValueGenerators $valueGenerators,
    ) {
    }

    /**
     * @return array<Stmt>
     */
    final public function generate(ValueMetadata $value, Context $context): array
    {
        $closure = new Expr\Closure([
            'static' => true,
            'params' => [
                new Param(new Expr\Variable('object'), type: new Identifier('object')),
                new Param(new Expr\Variable('context'), type: new  Name\FullyQualified(Context::class)),
                new Param(new Expr\Variable('output'), type: new Name\FullyQualified(OutputInterface::class)),
            ],
            'returnType' => new Identifier('void'),
            'stmts' => [
                ...$this->structureGenerator->generateBeginning(),
                ...$this->valueGenerators->for($value)->generate($value, new Expr\Variable('object')),
                ...$this->structureGenerator->generateEnding(),
            ],
        ]);

        $class = $value->class()->class;

        $comment = <<<TEXT
//
// {$class}
//
TEXT;

        foreach ($context as $i => $option) {
            $optionClass = $option::class;
            $optionValue = json_encode($option);

            $comment .= <<<TEXT

// {$optionClass}
// {$optionValue}
//
TEXT;
        }

        return [
            new Stmt\Return_($closure, [
                'comments' => [
                    new Comment\Doc($comment),
                ],
            ]),
        ];
    }
}
