<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Template\DictTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\ListTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\ObjectTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\ScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\TemplateGenerator;

abstract class TemplateGeneratorTestCase extends TestCase
{
    // TODO reset?
    protected static function createTemplateGeneratorStub(
        ScalarTemplateGenerator $scalarTemplateGenerator = null,
        ObjectTemplateGenerator $objectTemplateGenerator = null,
        ListTemplateGenerator $listTemplateGenerator = null,
        DictTemplateGenerator $dictTemplateGenerator = null,
    ): TemplateGenerator {
        $scalarTemplateGenerator ??= new ScalarTemplateGenerator(fn (NodeInterface $n) => $n);

        $objectTemplateGenerator ??= new ObjectTemplateGenerator(
            'BEFORE_PROPERTIES',
            'AFTER_PROPERTIES',
            'PROPERTY_SEPARATOR',
            'BEFORE_PROPERTY_NAME',
            'AFTER_PROPERTY_NAME',
            fn (string $s) => sprintf('ESCAPE(%s)', $s),
        );

        $listTemplateGenerator ??= new ListTemplateGenerator(
            'BEFORE_ITEMS',
            'AFTER_ITEMS',
            'ITEM_SEPARATOR',
        );

        $dictTemplateGenerator ??= new DictTemplateGenerator(
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
