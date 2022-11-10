<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller;

use Symfony\Polyfill\Marshaller\Template\Json\JsonTemplateGenerator;
use Symfony\Polyfill\Marshaller\Template\PhpWriterTrait;
use Symfony\Polyfill\Marshaller\Template\TemplateGeneratorInterface;
use Symfony\Polyfill\Marshaller\Metadata\Type;

/**
 * @internal
 */
final class TemplateGenerator
{
    use PhpWriterTrait;

    private const DEFAULT_CONTEXT = [
        'classes' => [],
        'reject_circular_reference' => true,
        'depth' => 0,
        'max_depth' => 512,
        'indentation_level' => 1,
        'hooks' => [],
        'enclosed' => true,
        'main_accessor' => '$data',
        'variables_counter' => [],
    ];

    /**
     * @var array<string, TemplateGeneratorInterface>
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

        $typeString = $type;
        $type = Type::fromString($typeString);

        $context = $context + self::DEFAULT_CONTEXT;
        $context['indentation_level'] = $context['enclosed'] ? 1 : 0;
        $accessor = $context['main_accessor'];

        $template = '';

        // TODO test
        if (true === ($context['root'] ?? true) && isset($context['hooks']['root'])) {
            return $context['hooks']['root']($class, $accessor, $context);
        }

        if ($type->isNullable()) {
            $template .= $this->writeLine("if (null === $accessor) {", $context);

            ++$context['indentation_level'];
            $template .= $this->templateGenerators[$format]->generateNull($context);

            --$context['indentation_level'];
            $template .= $this->writeLine('} else {', $context);

            ++$context['indentation_level'];
        }

        dd($type);
        $template .= match (true) {
            $type->isNull() => $this->templateGenerators[$format]->generateNull($context),
            $type->isScalar() => $this->templateGenerators[$format]->generateScalar($type, $accessor, $context),
            $type->isObject() => $this->templateGenerators[$format]->generateObject($type, $accessor, $context),
            default => throw new \InvalidArgumentException(sprintf('Cannot handle "%s" type', 'TODO')),
        };

        if ($type->isNullable()) {
            --$context['indentation_level'];
            $template .= self::writeLine('}', $context);
        }

        if (!$context['enclosed']) {
            return $template;
        }

        $body = $template;

        $context['indentation_level'] = 0;

        $template = $this->writeLine('<?php', $context);
        $template .= $this->writeLine('/**', $context);
        $template .= $this->writeLine(sprintf(' * @param %s $data', $typeString), $context);
        $template .= $this->writeLine(' * @param resource $resource', $context);
        $template .= $this->writeLine(' */', $context);
        $template .= $this->writeLine("return static function (mixed $accessor, \$resource, array \$context): void {", $context);
        $template .= $body;
        $template .= $this->writeLine('};', $context);

        return $template;
    }
}
