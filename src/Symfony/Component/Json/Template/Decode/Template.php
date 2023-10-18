<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Template\Decode;

use Psr\Container\ContainerInterface;
use Symfony\Component\Encoder\DataModel\Decode\DataModelBuilder;
use Symfony\Component\Encoder\Instantiator\InstantiatorInterface;
use Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface;
use Symfony\Component\Encoder\Stream\SeekableStreamInterface;
use Symfony\Component\Encoder\Stream\StreamReaderInterface;
use Symfony\Component\Json\Php\ClosureNode;
use Symfony\Component\Json\Php\Compiler;
use Symfony\Component\Json\Php\ExpressionNode;
use Symfony\Component\Json\Php\ParametersNode;
use Symfony\Component\Json\Php\PhpDocNode;
use Symfony\Component\Json\Php\ReturnNode;
use Symfony\Component\TypeInfo\Type;

/**
 * Provide path and contents of a decode template for a given type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type JsonDecodeConfig from JsonDecoder
 */
final readonly class Template
{
    public const DECODE_FROM_STRING = 'string';
    public const DECODE_FROM_STREAM = 'stream';
    public const DECODE_FROM_RESOURCE = 'resource';

    private TemplateGenerator $templateGenerator;
    private StreamTemplateGenerator $streamTemplateGenerator;

    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $cacheDir,
    ) {
        $this->templateGenerator = new TemplateGenerator();
        $this->streamTemplateGenerator = new StreamTemplateGenerator();
    }

    public function getPath(Type $type, string $decodeFrom): string
    {
        return sprintf('%s%s%s.decode.json.%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) $type), $decodeFrom);
    }

    /**
     * @param JsonDecodeConfig $config
     */
    public function generateContent(Type $type, array $config = []): string
    {
        $compiler = new Compiler();
        $dataModel = $this->dataModelBuilder->build($type, $config);

        $compiler->compile(new PhpDocNode([sprintf('@return %s', $type)]));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $compiler->indent();
        $bodyNodes = $this->templateGenerator->generate($dataModel, $config, []);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode(new ParametersNode([
            'string' => 'string',
            'config' => 'array',
            'instantiator' => '\\'.InstantiatorInterface::class,
            'services' => '?\\'.ContainerInterface::class,
        ]), 'mixed', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }

    /**
     * @param JsonDecodeConfig $config
     */
    public function generateStreamContent(Type $type, array $config = []): string
    {
        $compiler = new Compiler();
        $dataModel = $this->dataModelBuilder->build($type, $config);

        $compiler->compile(new PhpDocNode([sprintf('@return %s', $type)]));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $compiler->indent();
        $bodyNodes = $this->streamTemplateGenerator->generate($dataModel, $config, ['stream_type' => 'stream']);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode(new ParametersNode([
            'stream' => '\\'.StreamReaderInterface::class.'&\\'.SeekableStreamInterface::class,
            'config' => 'array',
            'instantiator' => '\\'.LazyInstantiatorInterface::class,
            'services' => '?\\'.ContainerInterface::class,
        ]), 'mixed', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }

    /**
     * @param JsonDecodeConfig $config
     */
    public function generateResourceContent(Type $type, array $config = []): string
    {
        $compiler = new Compiler();
        $dataModel = $this->dataModelBuilder->build($type, $config);

        $compiler->compile(new PhpDocNode(['@param resource $stream', sprintf('@return %s', $type)]));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $compiler->indent();
        $bodyNodes = $this->streamTemplateGenerator->generate($dataModel, $config, ['stream_type' => 'resource']);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode(new ParametersNode([
            'stream' => 'mixed',
            'config' => 'array',
            'instantiator' => '\\'.LazyInstantiatorInterface::class,
            'services' => '?\\'.ContainerInterface::class,
        ]), 'mixed', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
