<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize\Csv;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;
use Symfony\Component\SerDes\Type\TypeFactory;

use function Symfony\Component\SerDes\serialize_generate;

class SerializeGenerateTest extends TestCase
{
    /**
     * @dataProvider serializeGenerateDataProvider
     *
     * @param array<string, mixed> $context
     */
    public function testSerializeGenerate(string $expectedSource, string $type, array $context)
    {
        $this->assertSame($expectedSource, serialize_generate(TypeFactory::createFromString($type), 'csv', $context));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function serializeGenerateDataProvider(): iterable
    {
        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, null> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    \\fputcsv(\$resource, [null], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'array<int, null>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, int> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'array<int, int>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param mixed \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                if (!\\is_iterable(\$data)) {
                    throw new \\Symfony\\Component\\SerDes\\Exception\\UnexpectedValueException(\\sprintf("Expecting first level data type to be a list, but got \\"%s\\".", \\get_debug_type(\$data)));
                }
                if (\\is_iterable(\\reset(\$data))) {
                    \$headers_0 = \\array_reduce(\$data, static function (array \$c, array \$i): array {
                        return \\array_values(\\array_unique(\\array_merge(\$c, \array_keys(\$i))));
            }, []);
                    \$flippedHeaders_0 = \\array_fill_keys(\$headers_0, "");
                    \\fputcsv(\$resource, \$headers_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, \\array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } elseif (\\is_object(\\reset(\$data)) && \\is_subclass_of(\\get_class(\\reset(\$data)), "BackedEnum")) {
                    \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, [\$row_0->value], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } elseif (\\is_object(\\reset(\$data))) {
                    \\fputcsv(\$resource, \\array_keys((array) (\\reset(\$data))), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, (array) (\$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } else {
                    \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                }
            };

            PHP,
            'mixed',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                if (!\\is_iterable(\$data)) {
                    throw new \\Symfony\\Component\\SerDes\\Exception\\UnexpectedValueException(\\sprintf("Expecting first level data type to be a list, but got \\"%s\\".", \\get_debug_type(\$data)));
                }
                if (\\is_iterable(\\reset(\$data))) {
                    \$headers_0 = \\array_reduce(\$data, static function (array \$c, array \$i): array {
                        return \\array_values(\\array_unique(\\array_merge(\$c, \array_keys(\$i))));
            }, []);
                    \$flippedHeaders_0 = \\array_fill_keys(\$headers_0, "");
                    \\fputcsv(\$resource, \$headers_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, \\array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } elseif (\\is_object(\\reset(\$data)) && \\is_subclass_of(\\get_class(\\reset(\$data)), "BackedEnum")) {
                    \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, [\$row_0->value], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } elseif (\\is_object(\\reset(\$data))) {
                    \\fputcsv(\$resource, \\array_keys((array) (\\reset(\$data))), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, (array) (\$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } else {
                    \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                }
            };

            PHP,
            'array',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, array<int, int>> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fputcsv(\$resource, \\array_keys(\\reset(\$data)), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    \\fputcsv(\$resource, \$row_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'array<int, array<int, int>>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, array<string, int>> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \$headers_0 = \\array_reduce(\$data, static function (array \$c, array \$i): array {
                    return \\array_values(\\array_unique(\\array_merge(\$c, \\array_keys(\$i))));
            }, []);
                \$flippedHeaders_0 = \\array_fill_keys(\$headers_0, "");
                \\fputcsv(\$resource, \$headers_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    \\fputcsv(\$resource, \\array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'array<int, array<string, int>>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param iterable \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                if (!\\is_iterable(\$data)) {
                    throw new \\Symfony\\Component\\SerDes\\Exception\\UnexpectedValueException(\\sprintf("Expecting first level data type to be a list, but got \\"%s\\".", \\get_debug_type(\$data)));
                }
                if (\\is_iterable(\\reset(\$data))) {
                    \$headers_0 = \\array_reduce(\$data, static function (array \$c, array \$i): array {
                        return \\array_values(\\array_unique(\\array_merge(\$c, \array_keys(\$i))));
            }, []);
                    \$flippedHeaders_0 = \\array_fill_keys(\$headers_0, "");
                    \\fputcsv(\$resource, \$headers_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, \\array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } elseif (\\is_object(\\reset(\$data)) && \\is_subclass_of(\\get_class(\\reset(\$data)), "BackedEnum")) {
                    \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, [\$row_0->value], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } elseif (\\is_object(\\reset(\$data))) {
                    \\fputcsv(\$resource, \\array_keys((array) (\\reset(\$data))), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, (array) (\$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } else {
                    \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                }
            };

            PHP,
            'iterable',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param iterable<int, int> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'iterable<int, int>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param iterable<int, iterable<string, int>> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \$headers_0 = \\array_reduce(\$data, static function (array \$c, array \$i): array {
                    return \\array_values(\\array_unique(\\array_merge(\$c, \\array_keys(\$i))));
            }, []);
                \$flippedHeaders_0 = \\array_fill_keys(\$headers_0, "");
                \\fputcsv(\$resource, \$headers_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    \\fputcsv(\$resource, \\array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'iterable<int, iterable<string, int>>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param object \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                if (!\\is_iterable(\$data)) {
                    throw new \\Symfony\\Component\\SerDes\\Exception\\UnexpectedValueException(\\sprintf("Expecting first level data type to be a list, but got \\"%s\\".", \\get_debug_type(\$data)));
                }
                if (\\is_iterable(\\reset(\$data))) {
                    \$headers_0 = \\array_reduce(\$data, static function (array \$c, array \$i): array {
                        return \\array_values(\\array_unique(\\array_merge(\$c, \array_keys(\$i))));
            }, []);
                    \$flippedHeaders_0 = \\array_fill_keys(\$headers_0, "");
                    \\fputcsv(\$resource, \$headers_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, \\array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } elseif (\\is_object(\\reset(\$data)) && \\is_subclass_of(\\get_class(\\reset(\$data)), "BackedEnum")) {
                    \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, [\$row_0->value], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } elseif (\\is_object(\\reset(\$data))) {
                    \\fputcsv(\$resource, \\array_keys((array) (\\reset(\$data))), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, (array) (\$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                } else {
                    \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    foreach (\$data as \$row_0) {
                        \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                        \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                    }
                }
            };

            PHP,
            'object',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, Symfony\\Component\\SerDes\\Tests\\Fixtures\\Enum\\DummyBackedEnum> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    \\fputcsv(\$resource, [\$row_0->value], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            sprintf('array<int, %s>', DummyBackedEnum::class),
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, object> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \$headers_0 = \\array_keys((array) (\\reset(\$data)));
                \$flippedHeaders_0 = \\array_fill_keys(\$headers_0, "");
                \\fputcsv(\$resource, \$headers_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    if (\\is_iterable(\$row_0)) {
                        \\fputcsv(\$resource, \array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    } elseif (\\is_object(\$row_0) && \\is_subclass_of(\\get_class(\$row_0), "BackedEnum")) {
                        \\fputcsv(\$resource, [\$row_0->value], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    } elseif (\\is_object(\$row_0)) {
                        \\fputcsv(\$resource, (array) (\$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    } else {
                        \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    }
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'array<int, object>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \$headers_0 = ["id", "name"];
                \$flippedHeaders_0 = \\array_fill_keys(\$headers_0, "");
                \\fputcsv(\$resource, \$headers_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    \$object_0 = \$row_0;
                    \\fputcsv(\$resource, \array_replace(\$flippedHeaders_0, ["id" => \$object_0->id, "name" => \$object_0->name]), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            sprintf('array<int, %s>', ClassicDummy::class),
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, ?int> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    if (null === \$row_0) {
                        \\fputcsv(\$resource, [null], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    } else {
                        \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    }
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'array<int, ?int>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, int|string> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fputcsv(\$resource, [0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    if (\\is_int(\$row_0)) {
                        \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    } else {
                        \\fputcsv(\$resource, [\$row_0], \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    }
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'array<int, int|string>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, array<string, int|string|float>> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \$headers_0 = \\array_reduce(\$data, static function (array \$c, array \$i): array {
                    return \\array_values(\\array_unique(\\array_merge(\$c, \\array_keys(\$i))));
            }, []);
                \$flippedHeaders_0 = \\array_fill_keys(\$headers_0, "");
                \\fputcsv(\$resource, \$headers_0, \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                foreach (\$data as \$row_0) {
                    if (\\is_int(\$row_0)) {
                        \\fputcsv(\$resource, \\array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    } elseif (\\is_string(\$row_0)) {
                        \\fputcsv(\$resource, \\array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    } else {
                        \\fputcsv(\$resource, \\array_replace(\$flippedHeaders_0, \$row_0), \$context["csv_separator"] ?? ",", \$context["csv_enclosure"] ?? "\\"", \$context["csv_escape_char"] ?? "\\\\", "");
                    }
                    \\fwrite(\$resource, \$context["csv_end_of_line"] ?? "
            ");
                }
            };

            PHP,
            'array<int, array<string, int|string|float>>',
            [],
        ];
    }
}
