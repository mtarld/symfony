<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller;

use Symfony\Polyfill\Marshaller\Template\ObjectTemplateGenerator;
use Symfony\Polyfill\Marshaller\Template\ObjectTemplateGeneratorInterface;

/**
 * @internal
 */
final class TemplateGenerator
{
    private const DEFAULT_CONTEXT = [
        'classes' => [],
        'reject_circular_reference' => true,
        'depth' => 0,
        'max_depth' => 512,
        'indentation_level' => 1,
        'hooks' => [],
        'body_only' => false,
        'main_accessor' => '$object',
    ];

    /**
     * @param array<string, mixed> $context
     */
    public static function generate(\ReflectionClass $class, string $format, array $context): string
    {
        return match ($format) {
            'json' => self::generateJson($class, $context),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format))
        };
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function generateJson(\ReflectionClass $class, array $context): string
    {
        return self::doGenerate(ObjectTemplateGenerator\JsonObjectTemplateGenerator::class, $class, $context);
    }

    /**
     * @param class-string<ObjectTemplateGeneratorInterface> $objectTemplateGeneratorClass
     * @param array<string, mixed>                           $context
     */
    private static function doGenerate(string $objectTemplateGenerator, \ReflectionClass $class, array $context): string
    {
        $context = $context + self::DEFAULT_CONTEXT;

        $body = $objectTemplateGenerator::generate($class, $context['main_accessor'], $context);
        if ($context['body_only']) {
            return $body;
        }

        $template = '<?php'.PHP_EOL.PHP_EOL;
        $template .= '/** @param resource $resource */'.PHP_EOL;
        $template .= sprintf('return static function (object %s, $resource, array $context): void {%s', $context['main_accessor'], PHP_EOL);
        $template .= $body;
        $template .= '};'.PHP_EOL;

        return $template;
    }
}
