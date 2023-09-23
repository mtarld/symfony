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
use Symfony\Component\Json\JsonDecoder;
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
    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $cacheDir,
    ) {
    }

    public function getPath(Type $type, bool $forStream): string
    {
        return sprintf(
            '%s%s%s.decode.json%s.php',
            $this->cacheDir,
            \DIRECTORY_SEPARATOR,
            hash('xxh128', (string) $type),
            $forStream ? '.stream' : '',
        );
    }

    /**
     * @param JsonDecodeConfig $config
     */
    public function generateContent(Type $type, bool $forStream, array $config): string
    {
        $generator = $forStream ? new StreamTemplateGenerator() : new TemplateGenerator();

        $compiler = new Compiler();

        $compiler->compile(new PhpDocNode([
            '@param resource $resource',
            sprintf('@return %s', $type),
        ]));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $compiler->indent();
        $bodyNodes = $generator->generate($this->dataModelBuilder->build($type, $config), $config, []);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode(new ParametersNode([
            'resource' => 'mixed',
            'config' => 'array',
            'instantiator' => '\\'.InstantiatorInterface::class,
            'services' => '?\\'.ContainerInterface::class,
        ]), 'mixed', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }
}
