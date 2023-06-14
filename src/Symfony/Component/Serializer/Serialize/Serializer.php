<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize;

use Symfony\Component\Serializer\ContextInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Serialize\Dom\DomTreeBuilderInterface;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Compiler;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ArgumentsNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ClosureNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ExpressionNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\PhpDocNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ReturnNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\VariableNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\TemplateGeneratorInterface;
use Symfony\Component\Serializer\Serialize\Template\TemplateHelper;
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\Type;
use Symfony\Component\Serializer\Type\TypeFactory;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Serializer implements SerializerInterface
{
    private readonly TemplateHelper $templateHelper;

    /**
     * @param array<string, TemplateGeneratorInterface> $templateGenerators
     */
    public function __construct(
        private readonly DomTreeBuilderInterface $domTreeBuilder,
        private readonly array $templateGenerators,
        private readonly string $templateCacheDir,
    ) {
        $this->templateHelper = new TemplateHelper();
    }

    public function serialize(mixed $data, string $format, mixed $output, ContextInterface|array $context = []): void
    {
        if ($output instanceof StreamInterface) {
            $output = $output->resource();
        }

        if ($context instanceof ContextInterface) {
            $context = $context->toArray();
        }

        $context['type'] ??= get_debug_type($data);
        if (\is_string($context['type'])) {
            $context['type'] = TypeFactory::createFromString($context['type']);
        }

        $templatePath = $this->templateCacheDir.\DIRECTORY_SEPARATOR.$this->templateHelper->templateFilename($context['type'], $format, $context);

        if (!file_exists($templatePath) || ($context['force_generate_template'] ?? false)) {
            if (!file_exists($this->templateCacheDir)) {
                mkdir($this->templateCacheDir, recursive: true);
            }

            file_put_contents($templatePath, $this->template($context['type'], $format, $context));
        }

        (require $templatePath)($data, $output, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function template(Type $type, string $format, array $context): string
    {
        /** @var TemplateGeneratorInterface|null $templateGenerator */
        $templateGenerator = $this->templateGenerators[$format] ?? null;
        if (null === $templateGenerator) {
            throw new UnsupportedException(sprintf('"%s" format is not supported.', $format));
        }

        $compiler = new Compiler();
        $accessor = new VariableNode('data');

        $compiler->compile(new PhpDocNode([sprintf('@param %s $data', $type), '@param resource $resource']));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $argumentsNode = new ArgumentsNode(['data' => 'mixed', 'resource' => 'mixed', 'context' => 'array']);

        $compiler->indent();
        $bodyNodes = $templateGenerator->generate($this->domTreeBuilder->build($type, $accessor, $context), $context);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode($argumentsNode, 'void', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
