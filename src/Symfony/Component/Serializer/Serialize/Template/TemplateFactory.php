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
use Symfony\Component\Serializer\Serialize\Configuration\Configuration;
use Symfony\Component\Serializer\Serialize\Dom\DomTreeBuilderInterface;
use Symfony\Component\Serializer\Serialize\Php\ArgumentsNode;
use Symfony\Component\Serializer\Serialize\Php\ClosureNode;
use Symfony\Component\Serializer\Serialize\Php\Compiler;
use Symfony\Component\Serializer\Serialize\Php\ExpressionNode;
use Symfony\Component\Serializer\Serialize\Php\ReturnNode;
use Symfony\Component\Serializer\Serialize\Php\PhpDocNode;
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
        private readonly TemplateVariationExtractorInterface $templateVariationExtractor,
        private readonly array $templateGenerators,
        private readonly string $templateCacheDir,
    ) {
    }

    public function create(Type $type, string $format, Configuration $configuration): Template
    {
        return new Template(
            $this->path($type, $format, $configuration),
            fn () => $this->content($type, $format, $configuration),
        );
    }

    private function path(Type $type, string $format, Configuration $configuration): string
    {
        $hash = hash('xxh128', (string) $type);
        $variations = $this->templateVariationExtractor->extractFromConfiguration($configuration);

        if ([] !== $variations) {
            $hash .= '.'.hash('xxh128', implode('_', array_map(fn (TemplateVariation $t): string => (string) $t, $variations)));
        }

        return sprintf('%s%s%s.%s.php', $this->templateCacheDir, \DIRECTORY_SEPARATOR, $hash, $format);
    }

    private function content(Type $type, string $format, Configuration $configuration): string
    {
        /** @var TemplateGeneratorInterface|null $templateGenerator */
        $templateGenerator = $this->templateGenerators[$format] ?? null;
        if (null === $templateGenerator) {
            throw new UnsupportedException(sprintf('"%s" format is not supported.', $format));
        }

        $compiler = new Compiler();

        $compiler->compile(new PhpDocNode([sprintf('@param %s $data', $type), '@param resource $resource']));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $argumentsNode = new ArgumentsNode([
            'data' => 'mixed',
            'resource' => 'mixed',
            'configuration' => '\\'.Configuration::class,
        ]);

        $compiler->indent();

        $context = ['original_type' => $type];

        $bodyNodes = $templateGenerator->generate(
            $this->domTreeBuilder->build($type, '$data', $configuration, $context),
            $configuration,
            $context,
        );

        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode($argumentsNode, 'void', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
