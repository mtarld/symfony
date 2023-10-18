<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Template\Encode;

use Psr\Container\ContainerInterface;
use Symfony\Component\Encoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\Encoder\DataModel\VariableDataAccessor;
use Symfony\Component\Encoder\Stream\StreamWriterInterface;
use Symfony\Component\Json\JsonEncoder;
use Symfony\Component\Json\Php\ClosureNode;
use Symfony\Component\Json\Php\Compiler;
use Symfony\Component\Json\Php\ExpressionNode;
use Symfony\Component\Json\Php\ParametersNode;
use Symfony\Component\Json\Php\PhpDocNode;
use Symfony\Component\Json\Php\ReturnNode;
use Symfony\Component\TypeInfo\Type;

/**
 * Provide path and contents of a encode template for a given type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type JsonEncodeConfig from JsonEncoder
 */
final readonly class Template
{
    public const ENCODE_TO_STRING = 'string';
    public const ENCODE_TO_STREAM = 'stream';
    public const ENCODE_TO_RESOURCE = 'resource';

    private TemplateGenerator $templateGenerator;

    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $cacheDir,
    ) {
        $this->templateGenerator = new TemplateGenerator();
    }

    public function getPath(Type $type, string $encodeAs): string
    {
        return sprintf('%s%s%s.encode.json.%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) $type), $encodeAs);
    }

    /**
     * @param JsonEncodeConfig $config
     */
    public function generateContent(Type $type, array $config = []): string
    {
        $compiler = new Compiler();
        $dataModel = $this->dataModelBuilder->build($type, new VariableDataAccessor('data'), $config);

        $compiler->compile(new PhpDocNode([sprintf('@param %s $data', (string) $type)]));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $compiler->indent();
        $bodyNodes = $this->templateGenerator->generate($dataModel, $config, ['stream_type' => null]);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode(new ParametersNode([
            'data' => 'mixed',
            'config' => 'array',
            'services' => '?\\'.ContainerInterface::class,
        ]), '\\'.\Traversable::class, true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }

    /**
     * @param JsonEncodeConfig $config
     */
    public function generateStreamContent(Type $type, array $config = []): string
    {
        $compiler = new Compiler();
        $dataModel = $this->dataModelBuilder->build($type, new VariableDataAccessor('data'), $config);

        $compiler->compile(new PhpDocNode([sprintf('@param %s $data', (string) $type)]));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $compiler->indent();
        $bodyNodes = $this->templateGenerator->generate($dataModel, $config, ['stream_type' => 'stream']);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode(new ParametersNode([
            'data' => 'mixed',
            'stream' => '\\'.StreamWriterInterface::class,
            'config' => 'array',
            'services' => '?\\'.ContainerInterface::class,
        ]), 'void', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }

    /**
     * @param JsonEncodeConfig $config
     */
    public function generateResourceContent(Type $type, array $config = []): string
    {
        $compiler = new Compiler();
        $dataModel = $this->dataModelBuilder->build($type, new VariableDataAccessor('data'), $config);

        $compiler->compile(new PhpDocNode([sprintf('@param %s $data', (string) $type), '@param resource $stream']));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $compiler->indent();
        $bodyNodes = $this->templateGenerator->generate($dataModel, $config, ['stream_type' => 'resource']);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode(new ParametersNode([
            'data' => 'mixed',
            'stream' => 'mixed',
            'config' => 'array',
            'services' => '?\\'.ContainerInterface::class,
        ]), 'void', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
