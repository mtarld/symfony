<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller;

use Symfony\Polyfill\Marshaller\Template\Json\ObjectGenerator;

final class JsonTemplateGenerator
{
    public static function generate(\ReflectionClass $reflectionClass, array $context = []): string
    {
        $objectGenerator = new ObjectGenerator();

        $context['classes'] = [];
        $context['depth'] = 1;
        $context['max_depth'] = 512;
        $context['reject_circular_reference'] = 512;
        $context['indentation_level'] = 1;

        $template = '<?php' . PHP_EOL . PHP_EOL;
        $template .= '/** @param resource $resource */' . PHP_EOL;
        $template .= 'return static function (object $object, $resource, array $context): void {' . PHP_EOL;
        $template .= $objectGenerator->generate($reflectionClass, '$object', $context);
        $template .= '};' . PHP_EOL;

        return $template;
    }
}

