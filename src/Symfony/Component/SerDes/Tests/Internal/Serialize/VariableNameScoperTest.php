<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Internal\Serialize;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Internal\Serialize\VariableNameScoperTrait;

class VariableNameScoperTest extends TestCase
{
    public function testScopeVariableName()
    {
        $templateGenerator = new class() {
            use VariableNameScoperTrait {
                scopeVariableName as private doScopeVariableName;
            }

            public function scopeVariableName(string $prefix, array &$context): string
            {
                return $this->doScopeVariableName($prefix, $context);
            }
        };

        $context = [];

        $this->assertSame('foo_0', $templateGenerator->scopeVariableName('foo', $context));
        $this->assertSame('foo_1', $templateGenerator->scopeVariableName('foo', $context));
        $this->assertSame(['variable_counters' => ['foo' => 2]], $context);
    }
}
