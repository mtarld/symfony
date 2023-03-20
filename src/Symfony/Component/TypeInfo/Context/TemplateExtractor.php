<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Context;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\TypeInfo\Exception\LogicException;

/**
 * DO NOT REVIEW, this is part of another upcoming PR (work still in progress)
 */
final readonly class TemplateExtractor
{
    private PhpDocParser $phpstanDocParser;
    private Lexer $phpstanLexer;

    public function __construct()
    {
        $this->phpstanDocParser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
        $this->phpstanLexer = new Lexer();
    }

    public function getTemplates(\ReflectionClass $class): array
    {
        $templates = array_map(fn (TemplateTagValueNode $t): string => $t->name, $this->getTemplateNodes($class));

        if (array_unique($templates) !== $templates) {
            throw new LogicException(sprintf('Templates defined in "%s" must be unique.', $class->getName()));
        }

        return $templates;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<TemplateTagValueNode>
     */
    private function getTemplateNodes(\ReflectionClass $reflection): array
    {
        if (null === $rawDocNode = $reflection->getDocComment() ?: null) {
            return [];
        }

        $tokens = new TokenIterator($this->phpstanLexer->tokenize($rawDocNode));
        $docNode = $this->phpstanDocParser->parse($tokens);
        $tokens->consumeTokenType(Lexer::TOKEN_END);

        $tags = $docNode->getTagsByName('@template');

        return array_values(array_filter(
            array_map(fn (PhpDocTagNode $t): PhpDocTagValueNode => $t->value, $tags),
            fn (PhpDocTagValueNode $v): bool => $v instanceof TemplateTagValueNode,
        ));
    }
}
