<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Unmarshal\Template;

use Psr\Container\ContainerInterface;
use Symfony\Component\JsonMarshaller\JsonUnmarshaller;
use Symfony\Component\JsonMarshaller\Php\ClosureNode;
use Symfony\Component\JsonMarshaller\Php\Compiler;
use Symfony\Component\JsonMarshaller\Php\ExpressionNode;
use Symfony\Component\JsonMarshaller\Php\ParametersNode;
use Symfony\Component\JsonMarshaller\Php\PhpDocNode;
use Symfony\Component\JsonMarshaller\Php\ReturnNode;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelBuilder;
use Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface;

/**
 * Provide path and contents of a unmarshal template for a given type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type JsonUnmarshalConfig from JsonUnmarshaller
 */
final readonly class Template
{
    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $cacheDir,
    ) {
    }

    public function path(Type $type, bool $lazy): string
    {
        $hash = hash('xxh128', (string) $type);

        return sprintf(
            '%s%s%s.unmarshal.%s.json.php',
            $this->cacheDir,
            \DIRECTORY_SEPARATOR,
            $hash,
            $lazy ? 'lazy' : 'eager',
        );
    }

    /**
     * @param JsonUnmarshalConfig $config
     */
    public function content(Type $type, bool $lazy, array $config): string
    {
        $generator = $lazy ? new LazyTemplateGenerator() : new EagerTemplateGenerator();

        $compiler = new Compiler();

        $compiler->compile(new PhpDocNode([
            '@param resource $resource',
            sprintf('@return %s', $type),
        ]));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $parametersNode = new ParametersNode([
            'resource' => 'mixed',
            'config' => 'array',
            'instantiator' => '\\'.InstantiatorInterface::class,
            'services' => '\\'.ContainerInterface::class,
        ]);

        $compiler->indent();
        $bodyNodes = $generator->generate($this->dataModelBuilder->build($type, $config), $config, []);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode($parametersNode, 'mixed', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
