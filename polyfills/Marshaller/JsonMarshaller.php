<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller;

final class JsonMarshaller
{
    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    public static function marshal(object $object, $resource, array $context = []): void
    {
        $cachePath = $context['cache_path'] ?? sys_get_temp_dir();
        $cacheFilename = sprintf('%s%s%s.php', $cachePath, DIRECTORY_SEPARATOR, md5($object::class));

        // if (!file_exists($cacheFilename)) {
            $template = json_generate(new \ReflectionClass($object), $context);
        dd($template);
            file_put_contents($cacheFilename, $template);
        // }

        $marshal = require $cacheFilename;
        $marshal($object, $resource, $context);
    }
}
