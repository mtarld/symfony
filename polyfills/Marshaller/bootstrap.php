<?php

declare(strict_types=1);

use Symfony\Polyfill\Marshaller as p;

if (!function_exists('json_marshal')) {
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    function json_marshal(object $object, $resource, array $context = []): void { p\JsonMarshaller::marshal($object, $resource, $context); }
}

if (!function_exists('json_generate')) {
    /**
     * @param array<string, mixed> $context
     */
    function json_generate(\ReflectionClass $reflectionClass, array $context = []): string { return p\JsonTemplateGenerator::generate($reflectionClass, $context); }
}
