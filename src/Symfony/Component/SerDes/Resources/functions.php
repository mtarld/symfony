<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes;

use Symfony\Component\SerDes\Exception\PartialDeserializationException;
use Symfony\Component\SerDes\Internal\Deserialize\DeserializerFactory;
use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\Node\ArgumentsNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ClosureNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\PhpDocNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ReturnNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\VariableNode;
use Symfony\Component\SerDes\Internal\Serialize\TemplateGenerator\TemplateGeneratorFactory;
use Symfony\Component\SerDes\Template\TemplateHelper;
use Symfony\Component\SerDes\Type\Type;
use Symfony\Component\SerDes\Type\TypeFactory;
use Symfony\Component\SerDes\Type\UnionType;

if (!\function_exists(serialize::class)) {
    /**
     * @experimental in 7.0
     *
     * @param array<string, mixed> $context
     * @param resource             $resource
     */
    function serialize(mixed $data, $resource, string $format, array $context = []): void
    {
        $type = $context['type'] ?? get_debug_type($data);
        if (\is_string($type)) {
            $type = TypeFactory::createFromString($type);
        }

        $cacheDir = $context['cache_dir'] ?? sys_get_temp_dir().\DIRECTORY_SEPARATOR.'symfony_ser_des';
        $cacheFilename = (new TemplateHelper())->templateFilename($type, $format, $context);

        $cachePath = $cacheDir.\DIRECTORY_SEPARATOR.$cacheFilename;

        if (!file_exists($cachePath) || ($context['force_generate_template'] ?? false)) {
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, recursive: true);
            }

            file_put_contents($cachePath, serialize_generate($type, $format, $context));
        }

        (require $cachePath)($data, $resource, $context);
    }
}

if (!\function_exists(serialize_generate::class)) {
    /**
     * @experimental in 7.0
     *
     * @param array<string, mixed> $context
     */
    function serialize_generate(Type|UnionType $type, string $format, array $context = []): string
    {
        $compiler = new Compiler();
        $accessor = new VariableNode('data');

        $context = $context + [
            'generated_classes' => [],
            'variable_counters' => [],
        ];

        $compiler->compile(new PhpDocNode([sprintf('@param %s $data', $type), '@param resource $resource']));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $argumentsNode = new ArgumentsNode(['data' => 'mixed', 'resource' => 'mixed', 'context' => 'array']);

        $compiler->indent();
        $bodyNodes = TemplateGeneratorFactory::create($format)->generate($type, $accessor, $context);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode($argumentsNode, 'void', true, $bodyNodes))));
        $php = $compiler->source();

        return '<?php'.\PHP_EOL.\PHP_EOL.$phpDoc.$php;
    }
}

if (!\function_exists(deserialize::class)) {
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @throws PartialDeserializationException
     */
    function deserialize($resource, Type|UnionType $type, string $format, array $context = []): mixed
    {
        $errors = null;

        if ($context['collect_errors'] ?? false) {
            $errors = &$context['collected_errors'];
        }

        $context['lazy_reading'] = $context['lazy_reading'] ?? false;

        $result = DeserializerFactory::create($format, $context)->deserialize($resource, $type, $context);

        if (null !== $errors) {
            throw new PartialDeserializationException($resource, $result, $errors);
        }

        return $result;
    }
}
