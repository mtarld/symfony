<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Node\ArrayAccessNode;
use Symfony\Component\Marshaller\Native\Ast\Node\BinaryNode;
use Symfony\Component\Marshaller\Native\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;

final class TemplateGeneratorFactory
{
    private function __construct()
    {
    }

    public static function createJson(): TemplateGenerator
    {
        $scalarTemplateGenerator = new ScalarTemplateGenerator(
            valueEscaper: static function (NodeInterface $accessor): NodeInterface {
                return new FunctionNode('\json_encode', [
                    $accessor,
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
                ]);
            },
        );

        $objectTemplateGenerator = new ObjectTemplateGenerator(
            beforeProperties: '{',
            afterProperties: '}',
            propertySeparator: ',',
            beforePropertyName: '',
            afterPropertyName: ':',
            propertyNameEscaper: fn (string $s): string => json_encode($s) ?: $s,
        );

        $listTemplateGenerator = new ListTemplateGenerator(
            beforeItems: '[',
            afterItems: ']',
            itemSeparator: ',',
        );

        $dictTempateGenerator = new DictTemplateGenerator(
            beforeItems: '{',
            afterItems: '}',
            itemSeparator: ',',
            beforeKey: '',
            afterKey: ':',
            keyEscaper: static function (NodeInterface $key): NodeInterface {
                return new FunctionNode('\json_encode', [
                    $key,
                    new BinaryNode('??', new ArrayAccessNode(new VariableNode('context'), new ScalarNode('json_encode_flags')), new ScalarNode(0)),
                ]);
            },
        );

        return new TemplateGenerator($scalarTemplateGenerator, $objectTemplateGenerator, $listTemplateGenerator, $dictTempateGenerator);
    }
}
