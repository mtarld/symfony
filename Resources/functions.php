<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Exception\InvalidArgumentException;
use Symfony\Component\Marshaller\Exception\PartialUnmarshalException;
use Symfony\Component\Marshaller\Internal\Marshal\Compiler;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ArgumentsNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ClosureNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\PhpDocNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\ReturnNode;
use Symfony\Component\Marshaller\Internal\Marshal\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Marshal\TemplateGeneratorFactory;
use Symfony\Component\Marshaller\Internal\TypeFactory;
use Symfony\Component\Marshaller\Internal\Unmarshal\DecoderFactory;
use Symfony\Component\Marshaller\Internal\Unmarshal\UnmarshallerFactory;

if (!\function_exists('marshal')) {
    /**
     * @param array<string, mixed> $context
     * @param resource             $resource
     */
    function marshal(mixed $data, $resource, string $format, array $context = []): void
    {
        $type = $context['type'] ?? get_debug_type($data);

        $cacheDir = $context['cache_dir'] ?? sys_get_temp_dir().\DIRECTORY_SEPARATOR.'symfony_marshaller';
        $cacheFilename = sprintf('%s%s%s.%s.php', $cacheDir, \DIRECTORY_SEPARATOR, md5($type), $format);

        if (!file_exists($cacheFilename)) {
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, recursive: true);
            }

            file_put_contents($cacheFilename, marshal_generate($type, $format, $context));
        }

        (require $cacheFilename)($data, $resource, $context);
    }
}

if (!\function_exists('marshal_generate')) {
    /**
     * @param array<string, mixed> $context
     */
    function marshal_generate(string $type, string $format, array $context = []): string
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

if (!\function_exists('unmarshal')) {
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     *
     * @throws PartialUnmarshalException
     */
    function unmarshal($resource, string $type, string $format, array $context = []): mixed
    {
        if ($context['collect_errors'] ?? false) {
            $errors = &$context['collected_errors'];
        }

        $context['boundary'] = $context['boundary'] ?? [0, -1];
        $context['lazy_reading'] = $context['lazy_reading'] ?? true;

        $unmarshaller = UnmarshallerFactory::create($format, $context);
        $type = TypeFactory::createFromString($type);

        $result = match ($context['lazy_reading']) {
            true => $unmarshaller->unmarshal($resource, $type, $context),
            false => $unmarshaller->unmarshal(
                DecoderFactory::create($format)->decode($resource, $context['boundary'][0], $context['boundary'][1], $context),
                $type,
                $context,
            ),
            default => throw new InvalidArgumentException('Context value "lazy_reading" must be a boolean'),
        };

        if (isset($errors) && [] !== $errors) {
            throw new PartialUnmarshalException($resource, $result, $errors);
        }

        return $result;
    }
}
