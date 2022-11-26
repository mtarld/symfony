<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\ArgumentsNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ClosureNode;
use Symfony\Component\Marshaller\Native\Ast\Node\PhpDocNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ReturnNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @param array<string, mixed> $context
 * @param resource             $resource
 */
function marshal(mixed $data, $resource, string $format, array $context = []): void
{
    if (isset($context['type'])) {
        $type = $context['type'];
    } else {
        $builtinType = strtolower(gettype($data));

        $type = ['integer' => 'int', 'boolean' => 'bool', 'double' => 'float'][$builtinType] ?? $builtinType;
        if (is_object($data)) {
            $type = $data::class;
        }
    }

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

/**
 * @param array<string, mixed> $context
 */
function marshal_generate(string $type, string $format, array $context = []): string
{
    $jsonTemplateGenerator = new Template\Json\JsonTemplateGenerator();

    /** @var array<string, TemplateGenerator> $templateGenerators */
    $templateGenerators = [
        $jsonTemplateGenerator->format() => $jsonTemplateGenerator,
    ];

    if (!isset($templateGenerators[$format])) {
        throw new \InvalidArgumentException(sprintf('Unknown "%s" format.', $format));
    }

    $compiler = new Compiler();
    $type = Type::createFromString($type);
    $accessor = new VariableNode('data');

    $context = $context + [
        'generated_classes' => [],
        'hooks' => [],
        'variable_counters' => [],
    ];

    $compiler->compile(new PhpDocNode([sprintf('@param %s $data', (string) $type), '@param resource $resource']));
    $phpDoc = $compiler->source();
    $compiler->reset();

    $argumentsNode = new ArgumentsNode(['data' => 'mixed', 'resource' => null, 'context' => 'array']);

    $compiler->indent();
    $bodyNodes = $templateGenerators[$format]->generate($type, $accessor, $context);
    $compiler->outdent();

    $compiler->compile(new ReturnNode(new ClosureNode($argumentsNode, 'void', true, $bodyNodes)));
    $php = $compiler->source();

    return '<?php'.PHP_EOL.PHP_EOL.$phpDoc.$php;
}
