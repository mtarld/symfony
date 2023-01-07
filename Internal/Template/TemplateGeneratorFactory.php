<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Template;

use Symfony\Component\Marshaller\Exception\UnsupportedFormatException;
use Symfony\Component\Marshaller\Internal\Ast\Node\ArrayAccessNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\BinaryNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\FunctionNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\VariableNode;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TemplateGeneratorFactory
{
    private function __construct()
    {
    }

    public static function create(string $format): TemplateGenerator
    {
        return match ($format) {
            'json' => self::createJson(),
            default => throw new UnsupportedFormatException($format),
        };
    }

    private static function createJson(): TemplateGenerator
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
