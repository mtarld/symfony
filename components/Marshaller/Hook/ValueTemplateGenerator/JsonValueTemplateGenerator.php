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
            return self::generateDict($type->collectionKeyTypes(), $type->collectionValueTypes(), $accessor, $context);
        }

        if ($type->isList()) {
            return self::generateList($type->collectionValueTypes(), $accessor, $context);
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
     * @param list<Type>           $keyTypes
     * @param list<Type>           $valueTypes
     * @param array<string, mixed> $context
     */
    private static function generateDict(array $keyTypes, array $valueTypes, string $accessor, array $context): string
    {
        if (!UnionTypeChecker::isHomogenousKind($keyTypes)) {
            throw new \RuntimeException('Union type of collection key not homogenous.');
        }

        if (!UnionTypeChecker::isHomogenousKind($valueTypes)) {
            throw new \RuntimeException('Union type of collection value not homogenous.');
        }

        $keyType = $keyTypes[0];
        $valueType = $valueTypes[0];

        $fwrite = static function (string $content) use (&$context): string {
            return $context['fwrite']($content, $context);
        };
        $writeLine = static function (string $content) use (&$context): string {
            return $context['writeLine']($content, $context);
        };

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
     * @param list<Type>           $valueTypes
     * @param array<string, mixed> $context
     */
    private static function generateList(array $valueTypes, string $accessor, array $context): string
    {
        if (!UnionTypeChecker::isHomogenousKind($valueTypes)) {
            throw new \RuntimeException('Union type of collection value not homogenous.');
        }

        $valueType = $valueTypes[0];

        $fwrite = static function (string $content) use (&$context): string {
            return $context['fwrite']($content, $context);
        };
        $writeLine = static function (string $content) use (&$context): string {
            return $context['writeLine']($content, $context);
        };

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
