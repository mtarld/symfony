<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Template\TemplateVariation;

class TemplateVariationTest extends TestCase
{
    public function testCreateGroup()
    {
        $this->assertEquals(new TemplateVariation(type: 'group', value: 'foo'), TemplateVariation::createGroup('foo'));
    }

    public function testCompare()
    {
        $aa = new TemplateVariation(type: 'a', value: 'a');
        $ab = new TemplateVariation(type: 'a', value: 'b');
        $ba = new TemplateVariation(type: 'b', value: 'a');

        $this->assertSame(0, $aa->compare($aa));
        $this->assertSame(-1, $aa->compare($ab));
        $this->assertSame(-1, $aa->compare($ba));
        $this->assertSame(-1, $ab->compare($ba));
        $this->assertSame(1, $ba->compare($ab));
    }

    public function testToString()
    {
        $this->assertSame('type-value', (string) (new TemplateVariation(type: 'type', value: 'value')));
    }
}
