<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller;

/**
 * @internal
 */
final class Marshaller
{
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    public static function marshal(object $object, $resource, string $format, array $context): void
    {
        match ($format) {
            'json' => self::marshalJson($object, $resource, $context),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format))
        };
    }

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    public static function marshalJson(object $object, $resource, array $context): void
    {
        self::doMarshal(json_marshal_generate(...), $object, $resource, $context);
    }

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    private static function doMarshal(callable $templateGenerator, object $object, $resource, array $context): void
    {
        $cacheFilename = sprintf('%s%s%s.php', $context['cache_path'] ?? sys_get_temp_dir(), DIRECTORY_SEPARATOR, md5($object::class));

        if (!file_exists($cacheFilename)) {
            $template = $templateGenerator(new \ReflectionClass($object), $context);
            file_put_contents($cacheFilename, $template);
        }

        (require $cacheFilename)($object, $resource, $context);
    }
}
