<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\CircularReferenceException;
use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;

use function Symfony\Component\Marshaller\marshal_generate;

use Symfony\Component\Marshaller\Tests\Fixtures\Dto\CircularReferencingDummyLeft;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\CircularReferencingDummyRight;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Marshaller\Tests\Fixtures\Dto\SelfReferencingDummy;

final class MarshalGenerateTest extends TestCase
{
    /**
     * @dataProvider generateJsonTemplateDataProvider
     *
     * @param array<string, mixed> $context
     */
    public function testGenerateJsonTemplate(string $expectedSource, string $type, array $context): void
    {
        $this->assertSame($expectedSource, marshal_generate($type, 'json', $context));
    }

    /**
     * @return iterable<array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public function generateJsonTemplateDataProvider(): iterable
    {
        yield [
            <<<PHP
            <?php

            /**
             * @param null \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, \\json_encode(\$data, \$context["json_encode_flags"] ?? 0));
            };

            PHP, 'null', [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param int \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
            };

            PHP, 'int', [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param string \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
            };

            PHP, 'string', [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param bool \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
            };

            PHP, 'bool', [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, int> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, "[");
                \$prefix_0 = "";
                foreach (\$data as \$value_0) {
                    \\fwrite(\$resource, \$prefix_0);
                    \\fwrite(\$resource, \json_encode(\$value_0, \$context["json_encode_flags"] ?? 0));
                    \$prefix_0 = ",";
                }
                \\fwrite(\$resource, "]");
            };

            PHP, 'array<int, int>', [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<string, int> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, "{");
                \$prefix_0 = "";
                foreach (\$data as \$key_0 => \$value_0) {
                    \$key_0 = \json_encode(\$key_0, \$context["json_encode_flags"] ?? 0);
                    \\fwrite(\$resource, "{\$prefix_0}{\$key_0}:");
                    \\fwrite(\$resource, \json_encode(\$value_0, \$context["json_encode_flags"] ?? 0));
                    \$prefix_0 = ",";
                }
                \\fwrite(\$resource, "}");
            };

            PHP, 'array<string, int>', [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param iterable<int, int> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, "[");
                \$prefix_0 = "";
                foreach (\$data as \$value_0) {
                    \\fwrite(\$resource, \$prefix_0);
                    \\fwrite(\$resource, \json_encode(\$value_0, \$context["json_encode_flags"] ?? 0));
                    \$prefix_0 = ",";
                }
                \\fwrite(\$resource, "]");
            };

            PHP, 'iterable<int, int>', [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param iterable<string, int> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, "{");
                \$prefix_0 = "";
                foreach (\$data as \$key_0 => \$value_0) {
                    \$key_0 = \json_encode(\$key_0, \$context["json_encode_flags"] ?? 0);
                    \\fwrite(\$resource, "{\$prefix_0}{\$key_0}:");
                    \\fwrite(\$resource, \json_encode(\$value_0, \$context["json_encode_flags"] ?? 0));
                    \$prefix_0 = ",";
                }
                \\fwrite(\$resource, "}");
            };

            PHP, 'iterable<string, int>', [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \$object_0 = \$data;
                \\fwrite(\$resource, "{\"id\":");
                \\fwrite(\$resource, \json_encode(\$object_0->id, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, ",\"name\":");
                \\fwrite(\$resource, \json_encode(\$object_0->name, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, "}");
            };

            PHP, ClassicDummy::class, [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param array<int, Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy> \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
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

            PHP, sprintf('array<int, %s>', ClassicDummy::class), [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param ?int \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                if (null === \$data) {
                    \\fwrite(\$resource, \\json_encode(null, \$context["json_encode_flags"] ?? 0));
                } else {
                    \\fwrite(\$resource, \json_encode(\$data, \$context["json_encode_flags"] ?? 0));
                }
            };

            PHP, '?int', [], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param int \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \\fwrite(\$resource, \json_encode(\$foo, \$context["json_encode_flags"] ?? 0));
            };

            PHP, 'int', [
                'hooks' => [
                    'int' => static function (string $type, string $accessor, array $context): array {
                        return [
                            'type' => 'string',
                            'accessor' => '$foo',
                            'context' => $context,
                        ];
                    },
                ],
            ], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \$object_0 = \$data;
                \\fwrite(\$resource, "{\"foo\":");
                \\fwrite(\$resource, \json_encode(\$bar, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, ",\"name\":");
                \\fwrite(\$resource, \json_encode(\$object_0->name, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, "}");
            };

            PHP, ClassicDummy::class, [
                'hooks' => [
                    sprintf('%s::$id', ClassicDummy::class) => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                        return [
                            'name' => 'foo',
                            'type' => 'string',
                            'accessor' => '$bar',
                            'context' => $context,
                        ];
                    },
                ],
            ], ];

        yield [
            <<<PHP
            <?php

            /**
             * @param Symfony\Component\Marshaller\Tests\Fixtures\Dto\ClassicDummy \$data
             * @param resource \$resource
             */
            return static function (mixed \$data, \$resource, array \$context): void {
                \$object_0 = \$data;
                \\fwrite(\$resource, "{\"foo\":");
                \\fwrite(\$resource, \json_encode(\$foo, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, ",\"name\":");
                \\fwrite(\$resource, \json_encode(\$object_0->name, \$context["json_encode_flags"] ?? 0));
                \\fwrite(\$resource, "}");
            };

            PHP, ClassicDummy::class, [
                'hooks' => [
                    'int' => static function (string $type, string $accessor, array $context): array {
                        return [
                            'type' => 'bool',
                            'accessor' => '$foo',
                            'context' => $context,
                        ];
                    },
                    sprintf('%s::$id', ClassicDummy::class) => static function (\ReflectionProperty $property, string $accessor, array $context): array {
                        return [
                            'name' => 'foo',
                            'type' => 'int',
                            'accessor' => '$bar',
                            'context' => $context,
                        ];
                    },
                ],
            ], ];
    }

    /**
     * @dataProvider checkForCircularReferencesDataProvider
     */
    public function testCheckForCircularReferences(?string $expectedCircularClassName, string $type): void
    {
        if (null !== $expectedCircularClassName) {
            $this->expectException(CircularReferenceException::class);
            $this->expectExceptionMessage(sprintf('A circular reference has been detected on class "%s".', $expectedCircularClassName));
        }

        marshal_generate($type, 'json');

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<array{0: ?string, 1: string}>
     */
    public function checkForCircularReferencesDataProvider(): iterable
    {
        yield [null, ClassicDummy::class];
        yield [null, sprintf('array<int, %s>', ClassicDummy::class)];
        yield [null, sprintf('array<string, %s>', ClassicDummy::class)];
        yield [null, sprintf('%s|%1$s', ClassicDummy::class)];

        yield [SelfReferencingDummy::class, SelfReferencingDummy::class];
        yield [SelfReferencingDummy::class, sprintf('array<int, %s>', SelfReferencingDummy::class)];
        yield [SelfReferencingDummy::class, sprintf('array<string, %s>', SelfReferencingDummy::class)];
        yield [SelfReferencingDummy::class, sprintf('%s|%1$s', SelfReferencingDummy::class)];

        yield [CircularReferencingDummyLeft::class, CircularReferencingDummyLeft::class];
        yield [CircularReferencingDummyLeft::class, sprintf('array<int, %s>', CircularReferencingDummyLeft::class)];
        yield [CircularReferencingDummyLeft::class, sprintf('array<string, %s>', CircularReferencingDummyLeft::class)];
        yield [CircularReferencingDummyLeft::class, sprintf('%s|%1$s', CircularReferencingDummyLeft::class)];

        yield [CircularReferencingDummyRight::class, CircularReferencingDummyRight::class];
        yield [CircularReferencingDummyRight::class, sprintf('array<int, %s>', CircularReferencingDummyRight::class)];
        yield [CircularReferencingDummyRight::class, sprintf('array<string, %s>', CircularReferencingDummyRight::class)];
        yield [CircularReferencingDummyRight::class, sprintf('%s|%1$s', CircularReferencingDummyRight::class)];
    }

    public function testThrowOnUnknownFormat(): void
    {
        $this->expectException(UnsupportedFormatException::class);

        marshal_generate('int', 'unknown', []);
    }
}
