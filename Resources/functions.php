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
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\ArgumentsNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ClosureNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ExpressionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\PhpDocNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ReturnNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Internal\Decoder\Json\JsonDecoder;
use Symfony\Component\Marshaller\Internal\Denormalizer\Denormalizer;
use Symfony\Component\Marshaller\Internal\Parser\ParserFactory;
use Symfony\Component\Marshaller\Internal\Template\TemplateGeneratorFactory;
use Symfony\Component\Marshaller\Internal\Type\Type;

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

            $template = marshal_generate($type, $format, $context);
            file_put_contents($cacheFilename, $template);
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
        $type = Type::createFromString($type);
        $accessor = new VariableNode('data');

        $context = $context + [
            'generated_classes' => [],
            'variable_counters' => [],
        ];

        $compiler->compile(new PhpDocNode([sprintf('@param %s $data', (string) $type), '@param resource $resource']));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $argumentsNode = new ArgumentsNode(['data' => 'mixed', 'resource' => null, 'context' => 'array']);

        $compiler->indent();
        $bodyNodes = TemplateGeneratorFactory::create($format)->generate($type, $accessor, $context);
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

        $context['boundary'] = [
            'offset' => 0,
            'length' => -1,
        ];

        $context['mode'] = 'eager';

        $result = match ($mode = $context['mode'] ?? 'lazy') {
            'lazy' => ParserFactory::create($format)->parse($resource, Type::createFromString($type), $context),
            'eager' => (new Denormalizer())->denormalize((new JsonDecoder())->decode($resource, 0, -1, $context), Type::createFromString($type), $context),
            default => throw new InvalidArgumentException(sprintf('Invalid "%s" mode.', $mode)),
        };

        if (isset($errors) && [] !== $errors) {
            throw new PartialUnmarshalException($resource, $result, $errors);
        }

        return $result;
    }
}
