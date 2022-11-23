<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native;

use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @param array<string, mixed> $context
 * @param resource             $resource
 */
function marshal(mixed $data, $resource, string $format, array $context = []): void
{
    $builtinType = strtolower(gettype($data));

    $type = ['integer' => 'int', 'boolean' => 'bool', 'double' => 'float'][$builtinType] ?? $builtinType;
    if (is_object($data)) {
        $type = $data::class;
    }

    $type = isset($context['type']) ? $context['type'] : $type;

    $cachePath = $context['cache_dir'] ?? sys_get_temp_dir();
    $cacheFilename = sprintf('%s%s%s.%s.php', $cachePath, DIRECTORY_SEPARATOR, md5($type), $format);

    if (!file_exists($cacheFilename)) {
        if (!file_exists($cachePath)) {
            mkdir($cachePath, recursive: true);
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
        throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format));
    }

    $type = Type::createFromString($type);
    $context = $context + [
        'generated_classes' => [],
        'hooks' => [],
        'accessor' => '$data',
        'indentation_level' => 0,
        'variable_counters' => [],
        'enclosed' => true,
    ];

    $accessor = $context['accessor'];

    if (!$context['enclosed']) {
        return $templateGenerators[$format]->generate($type, $accessor, $context);
    }

    $template = '<?php'.PHP_EOL
        .'/**'.PHP_EOL
        .sprintf(' * @param %s $data', (string) $type).PHP_EOL
        .' * @param resource $resource'.PHP_EOL
        .' */'.PHP_EOL
        ."return static function (mixed $accessor, \$resource, array \$context): void {".PHP_EOL;

    ++$context['indentation_level'];

    $template .= $templateGenerators[$format]->generate($type, $accessor, $context);

    --$context['indentation_level'];

    return $template .= '};';
}
