<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Template\Encode;

use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Return_;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Psr\Container\ContainerInterface;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\JsonEncoder\DataModel\VariableDataAccessor;
use Symfony\Component\JsonEncoder\Stream\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * Provide path and contents of a encode template for a given type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class Template
{
    public const ENCODE_TO_STRING = 'string';
    public const ENCODE_TO_STREAM = 'stream';
    public const ENCODE_TO_RESOURCE = 'resource';

    private TemplateGenerator $templateGenerator;
    private Optimizer $optimizer;
    private BuilderFactory $builder;
    private PrettyPrinterAbstract $phpPrinter;

    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $cacheDir,
    ) {
        $this->templateGenerator = new TemplateGenerator();
        $this->optimizer = new Optimizer();
        $this->phpPrinter = new Standard();
        $this->builder = new BuilderFactory();
    }

    public function getPath(Type $type, string $encodeAs): string
    {
        return sprintf('%s%s%s.encode.json.%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) $type), $encodeAs);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function generateContent(Type $type, array $config = []): string
    {
        $dataModel = $this->dataModelBuilder->build($type, new VariableDataAccessor('data'), $config);

        $node = new Return_(new Closure([
            'static' => true,
            'params' => [
                new Param($this->builder->var('data'), type: 'mixed'),
                new Param($this->builder->var('config'), type: 'array'),
                new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
            ],
            'returnType' => new FullyQualified(\Traversable::class),
            'stmts' => $this->templateGenerator->generate($dataModel, $config, ['stream_type' => null]),
        ]));

        $nodes = $this->optimizer->optimize([$node]);

        return $this->phpPrinter->prettyPrintFile($nodes)."\n";
    }

    /**
     * @param array<string, mixed> $config
     */
    public function generateStreamContent(Type $type, array $config = []): string
    {
        $dataModel = $this->dataModelBuilder->build($type, new VariableDataAccessor('data'), $config);

        $node = new Return_(new Closure([
            'static' => true,
            'params' => [
                new Param($this->builder->var('data'), type: 'mixed'),
                new Param($this->builder->var('stream'), type: new FullyQualified(StreamWriterInterface::class)),
                new Param($this->builder->var('config'), type: 'array'),
                new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
            ],
            'returnType' => 'void',
            'stmts' => $this->templateGenerator->generate($dataModel, $config, ['stream_type' => 'stream']),
        ]));

        $nodes = $this->optimizer->optimize([$node]);

        return $this->phpPrinter->prettyPrintFile($nodes)."\n";
    }

    /**
     * @param array<string, mixed> $config
     */
    public function generateResourceContent(Type $type, array $config = []): string
    {
        $dataModel = $this->dataModelBuilder->build($type, new VariableDataAccessor('data'), $config);

        $node = new Return_(new Closure([
            'static' => true,
                'params' => [
                new Param($this->builder->var('data'), type: 'mixed'),
                new Param($this->builder->var('stream'), type: 'mixed'),
                new Param($this->builder->var('config'), type: 'array'),
                new Param($this->builder->var('services'), type: new NullableType(new FullyQualified(ContainerInterface::class))),
            ],
            'returnType' => 'void',
            'stmts' => $this->templateGenerator->generate($dataModel, $config, ['stream_type' => 'resource']),
        ]));

        $nodes = $this->optimizer->optimize([$node]);

        return $this->phpPrinter->prettyPrintFile($nodes)."\n";
    }
}
