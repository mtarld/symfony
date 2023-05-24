<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize\Json;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum;

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
        $this->assertSame($expectedSource, serialize_generate($type, 'json', $context));
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
             * @param null \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fwrite(\$resource, "null");
            };

            PHP,
            'null',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param int \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
            };

            PHP,
            'int',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param string \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
            };

            PHP,
            'string',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param bool \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
            };

            PHP,
            'bool',
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
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
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
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
            };

            PHP,
            'array',
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
                \\fwrite(\$resource, "[");
                \$prefix_0 = "";
                foreach (\$data as \$value_0) {
                    \\fwrite(\$resource, \$prefix_0);
                    \\fwrite(\$resource, \json_encode(\$value_0, \$context["json_encode_flags"] ?? 0));
                    \$prefix_0 = ",";
                }
                \\fwrite(\$resource, "]");
            };

            PHP,
            'array<int, int>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<string, int> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fwrite(\$resource, "{");
                \$prefix_0 = "";
                foreach (\$data as \$key_0 => \$value_0) {
                    \$key_0 = \substr(\json_encode(\$key_0, \$context["json_encode_flags"] ?? 0), 1, -1);
                    \\fwrite(\$resource, "{\$prefix_0}\"{\$key_0}\":");
                    \\fwrite(\$resource, \json_encode(\$value_0, \$context["json_encode_flags"] ?? 0));
                    \$prefix_0 = ",";
                }
                \\fwrite(\$resource, "}");
            };

            PHP,
            'array<string, int>',
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
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
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
                \\fwrite(\$resource, "[");
                \$prefix_0 = "";
                foreach (\$data as \$value_0) {
                    \\fwrite(\$resource, \$prefix_0);
                    \\fwrite(\$resource, \json_encode(\$value_0, \$context["json_encode_flags"] ?? 0));
                    \$prefix_0 = ",";
                }
                \\fwrite(\$resource, "]");
            };

            PHP,
            'iterable<int, int>',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param iterable<string, int> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fwrite(\$resource, "{");
                \$prefix_0 = "";
                foreach (\$data as \$key_0 => \$value_0) {
                    \$key_0 = \substr(\json_encode(\$key_0, \$context["json_encode_flags"] ?? 0), 1, -1);
                    \\fwrite(\$resource, "{\$prefix_0}\"{\$key_0}\":");
                    \\fwrite(\$resource, \json_encode(\$value_0, \$context["json_encode_flags"] ?? 0));
                    \$prefix_0 = ",";
                }
                \\fwrite(\$resource, "}");
            };

            PHP,
            'iterable<string, int>',
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
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
            };

            PHP,
            'object',
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param Symfony\Component\SerDes\Tests\Fixtures\Enum\DummyBackedEnum \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(\$data->value, \$context["json_encode_flags"] ?? 0));
            };

            PHP,
            DummyBackedEnum::class,
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param Symfony\Component\SerDes\Tests\Fixtures\Dto\ClassicDummy \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                \$object_0 = \$data;
                \\fwrite(\$resource, "{\"id\":");
                \\fwrite(\$resource, \json_encode(\$object_0->id, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, ",\"name\":");
                \\fwrite(\$resource, \json_encode(\$object_0->name, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, "}");
            };

            PHP,
            ClassicDummy::class,
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
                \\fwrite(\$resource, "[");
                \$prefix_0 = "";
                foreach (\$data as \$value_0) {
                    \\fwrite(\$resource, \$prefix_0);
                    \$object_0 = \$value_0;
                    \\fwrite(\$resource, "{\"id\":");
                    \\fwrite(\$resource, \json_encode(\$object_0->id, \$context["json_encode_flags"] ?? 0));
                    \\fwrite(\$resource, ",\"name\":");
                    \\fwrite(\$resource, \json_encode(\$object_0->name, \$context["json_encode_flags"] ?? 0));
                    \\fwrite(\$resource, "}");
                    \$prefix_0 = ",";
                }
                \\fwrite(\$resource, "]");
            };

            PHP,
            sprintf('array<int, %s>',
                ClassicDummy::class),
            [],
        ];

        yield [
            <<<PHP
            <?php

            /**
             * @param ?int \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, mixed \$resource, array \$context): void {
                if (null === \$data) {
                    \\fwrite(\$resource, "null");
                } else {
                    \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
                }
            };

            PHP,
            '?int',
            [],
        ];
    }
}
