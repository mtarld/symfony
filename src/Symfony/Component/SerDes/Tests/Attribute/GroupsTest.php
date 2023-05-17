<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Attribute\Groups;

class GroupsTest extends TestCase
{
    /**
     * @dataProvider constructDataProvider
     *
     * @param list<string>        $expectedGroups
     * @param string|list<string> $groups
     */
    public function testConstruct(array $expectedGroups, string|array $groups)
    {
        $this->assertSame($expectedGroups, (new Groups($groups))->groups);
    }

    /**
     * @return iterable<array{0: list<string>, 1: string|list<string>}>
     */
    public static function constructDataProvider(): iterable
    {
        yield [['a'], 'a'];
        yield [['a'], ['a']];
        yield [['a'], ['a', 'a']];
        yield [['a', 'b'], ['a', 'b', 'a']];
    }
}
