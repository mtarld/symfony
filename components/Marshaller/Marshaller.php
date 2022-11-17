<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\NativeContextBuilder\NativeContextBuilderInterface;
use Symfony\Component\Marshaller\Output\OutputInterface;
use Symfony\Component\Marshaller\Type\TypeFactory;

final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<NativeContextBuilderInterface> $nativeContextBuilders
     */
    public function __construct(
        private readonly iterable $nativeContextBuilders,
    ) {
    }

    public function marshal(mixed $data, string $format, OutputInterface $output, Context $context = null): void
    {
        marshal($data, $output->stream(), $format, $this->buildNativeContext($format, $context));
    }

    public function generate(string $type, string $format, Context $context = null): string
    {
        return marshal_generate($type, $format, $this->buildNativeContext($format, $context));
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function buildNativeContext(string $format, Context $context = null): array
    {
        $context = $context ?? new Context();
        $nativeContext = [];

        foreach ($this->nativeContextBuilders as $builder) {
            $nativeContext = $builder->build($format, $context, $nativeContext);
        }

        return $nativeContext;
    }
}

/**
 * @param array<string, mixed> $context
 * @param resource             $resource
 */
function marshal(mixed $data, $resource, string $format, array $context = []): void
{
    $nullablePrefix = true === ($context['nullable_data'] ?? false) ? '?' : '';

    $type = match (true) {
        isset($context['type']) => $nullablePrefix.$context['type'],
        is_object($data) => $nullablePrefix.$data::class,
        default => (static function (mixed $data): string {
            $type = strtolower(gettype($data));
            $typesMap = [
                'integer' => 'int',
                'boolean' => 'bool',
                'double' => 'float',
            ];

            return $nullablePrefix.($typesMap[$type] ?? $type);
        })(),
    };

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
    /** @var array<string, TemplateGenerator> $templateGenerators */
    $templateGenerators = [
        Template\Json\JsonTemplateGenerator::format() => new Template\Json\JsonTemplateGenerator(),
    ];

    if (!isset($templateGenerators[$format])) {
        throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format));
    }

    $type = TypeFactory::createFromString($type);
    $context = $context + [
        'generated_classes' => [],
        'hooks' => [],
        'accessor' => '$data',
        'indentation_level' => 0,
        'variable_counters' => [],
        'enclosed' => true,
        'validate_data' => false,
    ];

    $accessor = $context['accessor'];
    $context['readable_accessor'] = $context['readable_accessor'] ?? $accessor;

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
