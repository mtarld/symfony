<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

use Symfony\Component\Marshaller\Type\Type;


/**
 * Mimic marshal_generate template generation behavior.
 */
final class ValueTemplateGenerator
{
    /**
     * @param array<string, mixed> $context
     */
    public static function generateByType(Type $type, string $accessor, string $format, array $context): string
    {
        if ($type->isScalar()) {
            return self::generateScalar($accessor, $format, $context);
        }

        if ($type->isObject()) {
            return self::generateObject(new \ReflectionClass($type->className()), $accessor, $format, $context);
        }

        if ($type->isDict()) {
            return self::generateDict($accessor, $format, $context);
        }

        if ($type->isList()) {
            return self::generateDict($accessor, $format, $context);
        }

        throw new \LogicException(sprintf('Cannot handle "%s" type.', $type));
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateScalar(string $accessor, string $format, array $context): string
    {
        return match ($format) {
            'json' => $context['fwrite']("json_encode($accessor)", $context),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format)),
        };
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateObject(\ReflectionClass $class, string $accessor, string $format, array $context): string
    {
        ++$context['depth'];
        $context['body_only'] = true;
        $context['main_accessor'] = $accessor;

        return marshal_generate($class, $format, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateDict(string $accessor, string $format, array $context): string
    {
        return '';
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateList(string $accessor, string $format, array $context): string
    {
        return '';
    }
}
