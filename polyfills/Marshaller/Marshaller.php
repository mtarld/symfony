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
    public static function marshal(mixed $data, $resource, string $format, array $context): void
    {
        dd(strtolower(gettype($data)));
        // TODO depending on type
        $cacheFilename = sprintf('%s%s%s.%s.php', $context['cache_path'] ?? sys_get_temp_dir(), DIRECTORY_SEPARATOR, md5($object::class), $format);

        if (!file_exists($cacheFilename)) {
            if (!file_exists($context['cache_path'])) {
                mkdir($context['cache_path'], recursive: true);
            }

            dd(gettype($data));
            $template = marshal_generate(gettype($data), $format, $context);
            file_put_contents($cacheFilename, $template);
        }

        (require $cacheFilename)($object, $resource, $context);
    }
}
