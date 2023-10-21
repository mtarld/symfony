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

use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Psr\Container\ContainerInterface;
use Symfony\Component\Encoder\DataModel\Decode\DataModelBuilder;
use Symfony\Component\Encoder\Instantiator\InstantiatorInterface;
use Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface;
use Symfony\Component\Encoder\Stream\SeekableStreamInterface;
use Symfony\Component\Encoder\Stream\StreamReaderInterface;
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
    private BuilderFactory $builder;
    private PrettyPrinterAbstract $phpPrinter;

    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $cacheDir,
    ) {
        $this->templateGenerator = new TemplateGenerator();
        $this->streamTemplateGenerator = new StreamTemplateGenerator();
        $this->phpPrinter = new Standard();
        $this->builder = new BuilderFactory();
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
        $dataModel = $this->dataModelBuilder->build($type, $config);

        $node = new Return_(new Closure([
            'static' => true,
            'params' => [
                new Param($this->builder->var('string'), type: 'string'),
                new Param($this->builder->var('config'), type: 'array'),
                new Param($this->builder->var('instantiator'), type: new FullyQualified(InstantiatorInterface::class)),
                new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
            ],
            'returnType' => 'mixed',
            'stmts' => $this->templateGenerator->generate($dataModel, $config, []),
        ]));

        return $this->phpPrinter->prettyPrintFile([$node])."\n";
    }

    /**
     * @param JsonDecodeConfig $config
     */
    public function generateStreamContent(Type $type, array $config = []): string
    {
        $dataModel = $this->dataModelBuilder->build($type, $config);

        $node = new Return_(new Closure([
            'static' => true,
            'params' => [
                new Param($this->builder->var('stream'), type: new IntersectionType([
                    new FullyQualified(StreamReaderInterface::class),
                    new FullyQualified(SeekableStreamInterface::class),
                ])),
                new Param($this->builder->var('config'), type: 'array'),
                new Param($this->builder->var('instantiator'), type: new FullyQualified(LazyInstantiatorInterface::class)),
                new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
            ],
            'returnType' => 'mixed',
            'stmts' => $this->streamTemplateGenerator->generate($dataModel, $config, ['stream_type' => 'stream']),
        ]));

        return $this->phpPrinter->prettyPrintFile([$node])."\n";
    }

    /**
     * @param JsonDecodeConfig $config
     */
    public function generateResourceContent(Type $type, array $config = []): string
    {
        $dataModel = $this->dataModelBuilder->build($type, $config);

        $node = new Return_(new Closure([
            'static' => true,
            'params' => [
                new Param($this->builder->var('stream'), type: 'mixed'),
                new Param($this->builder->var('config'), type: 'array'),
                new Param($this->builder->var('instantiator'), type: new FullyQualified(LazyInstantiatorInterface::class)),
                new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
            ],
            'returnType' => 'mixed',
            'stmts' => $this->streamTemplateGenerator->generate($dataModel, $config, ['stream_type' => 'resource']),
        ]));

        return $this->phpPrinter->prettyPrintFile([$node])."\n";
    }
}
