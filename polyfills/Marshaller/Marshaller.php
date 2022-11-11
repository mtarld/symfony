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
    public function marshal(mixed $data, $resource, string $format, array $context): void
    {
        $type = $this->getType($data, $context);

        $cachePath = $context['cache_dir'] ?? sys_get_temp_dir();

        $cacheFilename = sprintf('%s%s%s.%s.php', $cachePath, DIRECTORY_SEPARATOR, md5($type), $format);

        if (!file_exists($cacheFilename)) {
            if (!file_exists($cachePath)) {
                mkdir($cachePath, recursive: true);
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

        $typesMap = [
            'integer' => 'int',
            'boolean' => 'bool',
            'double' => 'float',
        ];

        return $nullablePrefix.($typesMap[$type] ?? $type);
    }
}
