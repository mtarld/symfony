<?php

declare(strict_types=1);

use Symfony\Polyfill\Marshaller as p;

if (!function_exists('marshal')) {
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    function marshal(object $object, $resource, string $format, array $context = []): void { p\Marshaller::marshal($object, $resource, $format, $context); }
}

if (!function_exists('json_marshal')) {
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    function json_marshal(object $object, $resource, array $context = []): void { p\Marshaller::marshalJson($object, $resource, $context); }
}

if (!function_exists('marshal_generate')) {
    /**
     * @param array<string, mixed> $context
     */
    function marshal_generate(ReflectionClass $reflectionClass, string $format, array $context = []): string { return p\TemplateGenerator::generate($reflectionClass, $format, $context); }
}

if (!function_exists('json_marshal_generate')) {
    /**
     * @param array<string, mixed> $context
     */
    function json_marshal_generate(ReflectionClass $reflectionClass, array $context = []): string { return p\TemplateGenerator::generateJson($reflectionClass, $context); }
}
