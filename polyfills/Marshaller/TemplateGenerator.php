<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller;

use Symfony\Polyfill\Marshaller\Template\ObjectTemplateGenerator;
use Symfony\Polyfill\Marshaller\Template\ObjectTemplateGeneratorInterface;

final class TemplateGenerator
{
    private const DEFAULT_CONTEXT = [
        'classes' => [],
        'reject_circular_reference' => true,
        'depth' => 0,
        'max_depth' => 512,
        'indentation_level' => 1,
        'hooks' => [],
    ];

    /**
     * @param array<string, mixed> $context
     */
    public static function generate(\ReflectionClass $class, string $format, array $context): string
    {
        match ($format) {
            'json' => self::generateJson($object, $resource, $context),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format))
        };
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function generateJson(\ReflectionClass $class, array $context): string
    {
        return self::doGenerate(new ObjectTemplateGenerator\JsonObjectTemplateGenerator(), $class, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function doGenerate(ObjectTemplateGeneratorInterface $objectTemplateGenerator, \ReflectionClass $class, array $context): string
    {
        $template = '<?php'.PHP_EOL.PHP_EOL;
        $template .= '/** @param resource $resource */'.PHP_EOL;
        $template .= 'return static function (object $object, $resource, array $context): void {'.PHP_EOL;
        $template .= $objectTemplateGenerator->generate($class, '$object', $context + self::DEFAULT_CONTEXT);
        $template .= '};'.PHP_EOL;

        return $template;
    }
}
