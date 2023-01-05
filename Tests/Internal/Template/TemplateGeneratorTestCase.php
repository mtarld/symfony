<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Template\DictTemplateGenerator;
use Symfony\Component\Marshaller\Internal\Template\ListTemplateGenerator;
use Symfony\Component\Marshaller\Internal\Template\ObjectTemplateGenerator;
use Symfony\Component\Marshaller\Internal\Template\ScalarTemplateGenerator;
use Symfony\Component\Marshaller\Internal\Template\TemplateGenerator;

abstract class TemplateGeneratorTestCase extends TestCase
{
    protected static function createTemplateGeneratorStub(): TemplateGenerator
    {
        $scalarTemplateGenerator = new ScalarTemplateGenerator(fn (NodeInterface $n) => $n);

        $objectTemplateGenerator = new ObjectTemplateGenerator(
            'BEFORE_PROPERTIES',
            'AFTER_PROPERTIES',
            'PROPERTY_SEPARATOR',
            'BEFORE_PROPERTY_NAME',
            'AFTER_PROPERTY_NAME',
            fn (string $s) => sprintf('ESCAPE(%s)', $s),
        );

        $listTemplateGenerator = new ListTemplateGenerator(
            'BEFORE_ITEMS',
            'AFTER_ITEMS',
            'ITEM_SEPARATOR',
        );

        $dictTemplateGenerator = new DictTemplateGenerator(
            'BEFORE_ITEMS',
            'AFTER_ITEMS',
            'ITEM_SEPARATOR',
            'BEFORE_KEY',
            'AFTER_KEY',
            fn (NodeInterface $k) => $k,
        );

        return new TemplateGenerator($scalarTemplateGenerator, $objectTemplateGenerator, $listTemplateGenerator, $dictTemplateGenerator);
    }
}
