<?php

declare(strict_types=1);

use Symfony\Polyfill\Marshaller as p;

if (!function_exists('marshal')) {
    /**
     * @param array<string, mixed> $context
     */
    function marshal(mixed $data, $resource, string $format, array $context = []): void { (new p\Marshaller())->marshal($data, $resource, $format, $context); }
}

if (!function_exists('marshal_generate')) {
    /**
     * @param array<string, mixed> $context
     */
    function marshal_generate(string $type, string $format, array $context = []): string { return (new p\TemplateGenerator())->generate($type, $format, $context); }
}
