<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\ValueTemplateGenerator;

use Symfony\Component\Marshaller\Type\Type;
use Symfony\Component\Marshaller\Type\UnionTypeChecker;

/**
 * Mimic marshal_generate template generation to extend its behavior.
 */
final class JsonValueTemplateGenerator
{
    /**
     * @param array<string, mixed> $context
     */
    public static function generate(Type $type, string $accessor, array $context): string
    {
        if ($type->isScalar()) {
            return self::generateScalar($accessor, $context);
        }

        if ($type->isObject()) {
            return self::generateObject(new \ReflectionClass($type->className()), $accessor, $context);
        }

        if ($type->isDict()) {
            if (!UnionTypeChecker::isHomogenousKind($type->collectionKeyTypes())) {
                throw new \RuntimeException('Union type of collection key not homogenous.');
            }

            if (!UnionTypeChecker::isHomogenousKind($type->collectionValueTypes())) {
                throw new \RuntimeException('Union type of collection value not homogenous.');
            }

            return self::generateDict($type->collectionKeyTypes()[0], $type->collectionValueTypes()[0], $accessor, $context);
        }

        if ($type->isList()) {
            if (!UnionTypeChecker::isHomogenousKind($type->collectionValueTypes())) {
                throw new \RuntimeException('Union type of collection value not homogenous.');
            }

            return self::generateList($type->collectionValueTypes()[0], $accessor, $context);
        }

        throw new \LogicException(sprintf('Cannot handle "%s" type.', $type));
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateScalar(string $accessor, array $context): string
    {
        return $context['fwrite']("json_encode($accessor)", $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateObject(\ReflectionClass $class, string $accessor, array $context): string
    {
        ++$context['depth'];
        $context['body_only'] = true;
        $context['main_accessor'] = $accessor;

        return json_marshal_generate($class, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateDict(Type $keyType, Type $valueType, string $accessor, array $context): string
    {
        $fwrite = static function (string $content) use (&$context): string { return $context['fwrite']($content, $context); };
        $writeLine = static function (string $content) use (&$context): string { return $context['writeLine']($content, $context); };

        $template = $fwrite("'{'")
            .$writeLine("foreach ($accessor as \$key => \$value) {");

        ++$context['depth'];
        ++$context['indentation_level'];

        $template .= $fwrite("(\$prefix ?? '').json_encode(\$key).':'")
            .self::generate($valueType, '$value', $context)
            .$writeLine("\$prefix = ',';");

        --$context['depth'];
        --$context['indentation_level'];

        $template .= $writeLine('}')
            .$fwrite("'}'")
            .$writeLine('unset($prefix);');

        return $template;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateList(Type $valueType, string $accessor, array $context): string
    {
        $fwrite = static function (string $content) use (&$context): string { return $context['fwrite']($content, $context); };
        $writeLine = static function (string $content) use (&$context): string { return $context['writeLine']($content, $context); };

        $template = $fwrite("'['")
            .$writeLine("foreach ($accessor as \$item) {");

        ++$context['depth'];
        ++$context['indentation_level'];

        $template .= $fwrite("\$prefix ?? ''")
            .self::generate($valueType, '$item', $context)
            .$writeLine("\$prefix = ',';");

        --$context['depth'];
        --$context['indentation_level'];

        $template .= $writeLine('}')
            .$fwrite("']'")
            .$writeLine('unset($prefix);');

        return $template;
    }
}
