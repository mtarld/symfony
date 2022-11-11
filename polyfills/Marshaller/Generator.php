<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller;

use Symfony\Polyfill\Marshaller\Metadata\TypeFactory;
use Symfony\Polyfill\Marshaller\Template\Json\JsonTemplateGenerator;
use Symfony\Polyfill\Marshaller\Template\PhpWriterTrait;
use Symfony\Polyfill\Marshaller\Template\TemplateGenerator;

/**
 * @internal
 */
final class Generator
{
    use PhpWriterTrait;

    private const DEFAULT_CONTEXT = [
        'classes' => [],
        'hooks' => [],
        'main_accessor' => '$data',
        'indentation_level' => 0,
        'variable_counters' => [],
        'enclosed' => true,
    ];

    /**
     * @var array<string, TemplateGenerator>
     */
    private readonly array $templateGenerators;

    public function __construct()
    {
        $this->templateGenerators = [
            'json' => new JsonTemplateGenerator(),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function generate(string $type, string $format, array $context): string
    {
        if (!isset($this->templateGenerators[$format])) {
            throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format));
        }

        $type = TypeFactory::createFromString($type);

        $context = $context + self::DEFAULT_CONTEXT;

        if (true === ($context['root'] ?? true) && isset($context['hooks']['root'])) {
            $context['root'] = false;

            return $context['hooks']['root']((string) $type, $format, $context);
        }

        $accessor = $context['main_accessor'];

        $template = $this->writeLine('<?php', $context)
            .$this->writeLine('/**', $context)
            .$this->writeLine(sprintf(' * @param %s $data', (string) $type), $context)
            .$this->writeLine(' * @param resource $resource', $context)
            .$this->writeLine(' */', $context)
            .$this->writeLine("return static function (mixed $accessor, \$resource, array \$context): void {", $context);

        ++$context['indentation_level'];

        $template .= $this->templateGenerators[$format]->generate($type, $accessor, $context);

        --$context['indentation_level'];

        return $template .= $this->writeLine('};', $context);
    }
}
