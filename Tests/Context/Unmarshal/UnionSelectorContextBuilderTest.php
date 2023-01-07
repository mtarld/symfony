<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context\Unmarshal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\UnionSelectorOption;
use Symfony\Component\Marshaller\Context\Unmarshal\UnionSelectorContextBuilder;

final class UnionSelectorContextBuilderTest extends TestCase
{
    public function testAddUnionSelectorToContext(): void
    {
        $unionSelectorOption = new UnionSelectorOption(['int|string' => 'string']);
        $rawContext = (new UnionSelectorContextBuilder())->build('useless', new Context($unionSelectorOption), []);

        $this->assertEquals(['union_selector' => ['int|string' => 'string']], $rawContext);
    }
}
