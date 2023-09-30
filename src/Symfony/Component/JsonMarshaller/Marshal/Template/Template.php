<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Marshal\Template;

use Psr\Container\ContainerInterface;
use Symfony\Component\JsonMarshaller\JsonMarshaller;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelBuilder;
use Symfony\Component\JsonMarshaller\Php\ClosureNode;
use Symfony\Component\JsonMarshaller\Php\Compiler;
use Symfony\Component\JsonMarshaller\Php\ExpressionNode;
use Symfony\Component\JsonMarshaller\Php\ParametersNode;
use Symfony\Component\JsonMarshaller\Php\PhpDocNode;
use Symfony\Component\JsonMarshaller\Php\ReturnNode;
use Symfony\Component\JsonMarshaller\Php\VariableNode;
use Symfony\Component\JsonMarshaller\Type\Type;

/**
 * Provide path and contents of a marshal template for a given type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type JsonMarshalConfig from JsonMarshaller
 */
final readonly class Template
{
    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $cacheDir,
    ) {
    }

    public function path(Type $type): string
    {
        return sprintf('%s%s%s.marshal.json.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) $type));
    }

    /**
     * @param JsonMarshalConfig $config
     */
    public function content(Type $type, array $config = []): string
    {
        $compiler = new Compiler();

        $compiler->compile(new PhpDocNode([
            sprintf('@param %s $data', $type),
            '@param resource $resource',
        ]));
        $phpDoc = $compiler->source();

        $compiler->reset();

        $argumentsNode = new ParametersNode([
            'data' => 'mixed',
            'resource' => 'mixed',
            'config' => 'array',
            'services' => '?\\'.ContainerInterface::class,
        ]);

        $compiler->indent();
        $bodyNodes = (new TemplateGenerator())->generate($this->dataModelBuilder->build($type, new VariableNode('data'), $config), $config, []);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode($argumentsNode, 'void', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
