<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook\ValueTemplateGenerator;

use Symfony\Component\Marshaller\Type\Types;

/**
 * Mimic marshal_generate template generation to extend its behavior.
 */
final class JsonValueTemplateGenerator
{
    /**
     * @param array<string, mixed> $context
     */
    public static function generate(Types $types, string $accessor, array $context): string
    {
        if ($types->isOnlyScalar()) {
            return self::generateScalar($accessor, $context);
        }

        if ($types->isOnlyObject() && $types->isSameClass()) {
            return self::generateObject($types, $accessor, $context);
        }

        if ($types->isOnlyDict()) {
            return self::generateDict($types->types[0]->collectionValueTypes(), $accessor, $context);
        }

        if ($types->isOnlyList()) {
            return self::generateList($types->types[0]->collectionValueTypes(), $accessor, $context);
        }

        throw new \LogicException(sprintf('Cannot handle "%s" type.', $types));
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
    private static function generateObject(Types $types, string $accessor, array $context): string
    {
        $template = '';

        if ($types->isNullable()) {
            $template .= $context['writeLine']("if (null === $accessor) {", $context);

            ++$context['indentation_level'];
            $template .= $context['fwrite']("'null'", $context);

            --$context['indentation_level'];
            $template .= $context['writeLine']('} else {', $context);

            ++$context['indentation_level'];
        }

        ++$context['depth'];
        $context['enclosed'] = false;
        $context['main_accessor'] = $accessor;

        if ('' === $value = json_marshal_generate(new \ReflectionClass($types->types[0]->className()), $context)) {
            return '';
        }

        $template .= $value;

        if ($types->isNullable()) {
            --$context['indentation_level'];
            $template .= $context['writeLine']('}', $context);
        }

        return $template;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateDict(Types $valueTypes, string $accessor, array $context): string
    {
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
            .self::generate($valueTypes, $valueName, $context)
            .$writeLine("$prefixName = ',';");

        --$context['depth'];
        --$context['indentation_level'];

        $template .= $writeLine('}')
            .$fwrite("'}'")
            .$writeLine("unset($prefixName);");

        return $template;
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function generateList(Types $valueTypes, string $accessor, array $context): string
    {
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
            .self::generate($valueTypes, $valueName, $context)
            .$writeLine("$prefixName = ',';");

        --$context['depth'];
        --$context['indentation_level'];

        $template .= $writeLine('}')
            .$fwrite("']'")
            .$writeLine("unset($prefixName);");

        return $template;
    }
}
