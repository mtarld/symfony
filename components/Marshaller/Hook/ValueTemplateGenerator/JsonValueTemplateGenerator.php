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
            return self::generateObject($type, $accessor, $context);
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
    private static function generateObject(Type $type, string $accessor, array $context): string
    {
        $template = '';

        if ($type->isNullable()) {
            $template .= $context['writeLine']("if (null === $accessor) {", $context);

            ++$context['indentation_level'];
            $template .= $context['fwrite']("'null'", $context);

            --$context['indentation_level'];
            $template .= $context['writeLine']('} else {', $context);

            ++$context['indentation_level'];
        }

        ++$context['depth'];
        $context['body_only'] = true;
        $context['main_accessor'] = $accessor;

        $template .= json_marshal_generate(new \ReflectionClass($type->className()), $context);

        if ($type->isNullable()) {
            --$context['indentation_level'];
            $template .= $context['writeLine']('}', $context);
        }

        return $template;
    }

    /**
     * @param list<Type>           $keyTypes
     * @param list<Type>           $valueTypes
     * @param array<string, mixed> $context
     */
    private static function generateDict(array $keyTypes, array $valueTypes, string $accessor, array $context): string
    {
        if (!UnionTypeChecker::isHomogenousKind($keyTypes)) {
            throw new \RuntimeException('Collection key type is not homogenous.');
        }

        if (!UnionTypeChecker::isHomogenousKind($valueTypes)) {
            throw new \RuntimeException('Collection value type is not homogenous.');
        }

        $keyType = $keyTypes[0];
        $valueType = $valueTypes[0];

        $fwrite = static function (string $content) use (&$context): string {
            return $context['fwrite']($content, $context);
        };
        $writeLine = static function (string $content) use (&$context): string {
            return $context['writeLine']($content, $context);
        };

        $prefixName = '$'.uniqid('prefix');
        $keyName = '$'.uniqid('key');
        $valueName = '$'.uniqid('value');

        $template = $fwrite("'{'")
            .$writeLine("$prefixName = '';")
            .$writeLine("foreach ($accessor as $keyName => $valueName) {");

        ++$context['depth'];
        ++$context['indentation_level'];

        $template .= $fwrite("$prefixName.json_encode($keyName).':'")
            .self::generate($valueType, $valueName, $context)
            .$writeLine("$prefixName = ',';");

        --$context['depth'];
        --$context['indentation_level'];

        $template .= $writeLine('}')
            .$fwrite("'}'")
            .$writeLine("unset($prefixName);");

        return $template;
    }

    /**
     * @param list<Type>           $valueTypes
     * @param array<string, mixed> $context
     */
    private static function generateList(array $valueTypes, string $accessor, array $context): string
    {
        if (!UnionTypeChecker::isHomogenousKind($valueTypes)) {
            throw new \RuntimeException('Collection value type is not homogenous.');
        }

        $valueType = $valueTypes[0];

        $fwrite = static function (string $content) use (&$context): string {
            return $context['fwrite']($content, $context);
        };
        $writeLine = static function (string $content) use (&$context): string {
            return $context['writeLine']($content, $context);
        };

        $prefixName = '$'.uniqid('prefix');
        $valueName = '$'.uniqid('value');

        $template = $fwrite("'['")
            .$writeLine("$prefixName = '';")
            .$writeLine("foreach ($accessor as $valueName) {");

        ++$context['depth'];
        ++$context['indentation_level'];

        $template .= $fwrite($prefixName)
            .self::generate($valueType, $valueName, $context)
            .$writeLine("$prefixName = ',';");

        --$context['depth'];
        --$context['indentation_level'];

        $template .= $writeLine('}')
            .$fwrite("']'")
            .$writeLine("unset($prefixName);");

        return $template;
    }
}
