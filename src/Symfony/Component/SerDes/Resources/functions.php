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

use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Exception\PartialDeserializationException;
use Symfony\Component\SerDes\Internal\Deserialize\DecoderFactory;
use Symfony\Component\SerDes\Internal\Deserialize\DeserializerFactory;
use Symfony\Component\SerDes\Internal\Serialize\Compiler;
use Symfony\Component\SerDes\Internal\Serialize\Node\ArgumentsNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ClosureNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ExpressionNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\PhpDocNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\ReturnNode;
use Symfony\Component\SerDes\Internal\Serialize\Node\VariableNode;
use Symfony\Component\SerDes\Internal\Serialize\TemplateGeneratorFactory;
use Symfony\Component\SerDes\Internal\TypeFactory;

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

        $cacheDir = $context['cache_dir'] ?? sys_get_temp_dir().\DIRECTORY_SEPARATOR.'symfony_ser_des';
        $cacheFilename = sprintf('%s%s%s.%s.php', $cacheDir, \DIRECTORY_SEPARATOR, md5($type), $format);

        if (!file_exists($cacheFilename)) {
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, recursive: true);
            }

            file_put_contents($cacheFilename, serialize_generate($type, $format, $context));
        }

        (require $cacheFilename)($data, $resource, $context);
    }
}

if (!\function_exists(serialize_generate::class)) {
    /**
     * @experimental in 7.0
     *
     * @param array<string, mixed> $context
     */
    function serialize_generate(string $type, string $format, array $context = []): string
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
        $bodyNodes = TemplateGeneratorFactory::create($format)->generate(TypeFactory::createFromString($type), $accessor, $context);
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
    function deserialize($resource, string $type, string $format, array $context = []): mixed
    {
        $errors = [];

        if ($context['collect_errors'] ?? false) {
            $errors = &$context['collected_errors'];
        }

        $context['boundary'] = $context['boundary'] ?? [0, -1];
        $context['lazy_reading'] = $context['lazy_reading'] ?? false;

        $deserializer = DeserializerFactory::create($format, $context);
        $type = TypeFactory::createFromString($type);

        $result = match ($context['lazy_reading']) {
            true => $deserializer->deserialize($resource, $type, $context),
            false => $deserializer->deserialize(
                DecoderFactory::create($format)->decode($resource, $context['boundary'][0], $context['boundary'][1], $context),
                $type,
                $context,
            ),
            default => throw new InvalidArgumentException('Context value "lazy_reading" must be a boolean'),
        };

        if ([] !== $errors) {
            throw new PartialDeserializationException($resource, $result, $errors);
        }

        return $result;
    }
}
