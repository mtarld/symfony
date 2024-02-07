<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\BackwardCompatibilityHelper;
use Symfony\Component\TypeInfo\Type;

/**
 * @group legacy
 */
class BackwardCompatibilityHelperTest extends TestCase
{
    /**
     * @dataProvider convertTypeToLegacyTypesDataProvider
     *
     * @param list<LegacyType>|null $legacyTypes
     */
    public function convertTypeToLegacyTypes(?array $legacyTypes, Type $type, bool $keepNullType = true)
    {
        $this->assertEquals($legacyTypes, BackwardCompatibilityHelper::convertTypeToLegacyTypes($type, $keepNullType));
    }

    /**
     * @return iterable<array{0: list<LegacyType>|null, 1: Type, 2?: bool}>
     */
    public function convertTypeToLegacyTypesDataProvider(): iterable
    {
        return [];
    }
}
