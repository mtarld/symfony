<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Template;

use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Serialize\Dom\DomTreeBuilderInterface;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Compiler;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ArgumentsNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ClosureNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ExpressionNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\PhpDocNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ReturnNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\TemplateGeneratorInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class TemplateFactory
{
    /**
     * @param array<string, TemplateGeneratorInterface> $templateGenerators
     */
    public function __construct(
        private readonly DomTreeBuilderInterface $domTreeBuilder,
        private readonly TemplateVariantConverter $templateVariantConverter,
        private readonly array $templateGenerators,
        private readonly string $templateCacheDir,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function create(Type $type, string $format, array $context): Template
    {
        return new Template(
            $this->path($type, $format, $context),
            fn () => $this->content($type, $format, $context),
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function path(Type $type, string $format, array $context): string
    {
        $hash = hash('xxh128', (string) $type);

        if ($variant = $this->templateVariantConverter->fromContext($context)) {
            $hash .= '.'.hash('xxh128', implode('_', array_map(fn (TemplateVariation $t): string => (string) $t, $variant->variations)));
        }

        return sprintf('%s%s%s.%s.php', $this->templateCacheDir, \DIRECTORY_SEPARATOR, $hash, $format);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function content(Type $type, string $format, array $context): string
    {
        $context['type'] = $type;

        /** @var TemplateGeneratorInterface|null $templateGenerator */
        $templateGenerator = $this->templateGenerators[$format] ?? null;
        if (null === $templateGenerator) {
            throw new UnsupportedException(sprintf('"%s" format is not supported.', $format));
        }

        $compiler = new Compiler();

        $compiler->compile(new PhpDocNode([sprintf('@param %s $data', $type), '@param resource $resource']));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $argumentsNode = new ArgumentsNode(['data' => 'mixed', 'resource' => 'mixed', 'context' => 'array']);

        $compiler->indent();
        $bodyNodes = $templateGenerator->generate($this->domTreeBuilder->build($type, '$data', $context), $context);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode($argumentsNode, 'void', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
