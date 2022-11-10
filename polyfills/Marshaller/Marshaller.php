<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller;

/**
 * @internal
 */
final class Marshaller
{
    private const MAP_TYPES = [
        'integer' => 'int',
        'boolean' => 'bool',
        'double' => 'float',
    ];

    /**
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    public function marshal(mixed $data, $resource, string $format, array $context): void
    {
        $type = $this->getType($data, $context);

        $cacheFilename = sprintf('%s%s%s.%s.php', $context['cache_path'] ?? sys_get_temp_dir(), DIRECTORY_SEPARATOR, md5($type), $format);

        if (!file_exists($cacheFilename)) {
            if (!file_exists($context['cache_path'])) {
                mkdir($context['cache_path'], recursive: true);
            }


            $template = marshal_generate($type, $format, $context);
            file_put_contents($cacheFilename, $template);
        }

        (require $cacheFilename)($data, $resource, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function getType(mixed $data, array $context): string
    {
        $nullablePrefix = true === ($context['nullable_data'] ?? false) ? '?' : '';

        if (null !== ($type = $context['type'] ?? null)) {
            return $nullablePrefix.$type;
        }

        if (is_object($data)) {
            return $nullablePrefix.$data::class;
        }

        $type = strtolower(gettype($data));

        return $nullablePrefix.(self::MAP_TYPES[$type] ?? $type);
    }
}
