<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Deserialize\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Deserialize\Context\DeserializeContext;
use Symfony\Component\Serializer\Deserialize\Hook\ObjectHookInterface;

class DeserializeContextTest extends TestCase
{
    public function testWithers()
    {
        $hook = $this->createStub(ObjectHookInterface::class);

        $context = (new DeserializeContext(['constructor_option' => true]))
            ->withGroups(['groupOne', 'groupTwo'])
            ->withCollectErrors()
            ->withJsonDecodeFlags(123)
            ->withUnionSelector(['int|string' => 'int'])
            ->withHook($hook)
            ->withHook($hook, 'className')
            ->withEagerUnmarshal()
            ->withLazyInstantiation();

        $this->assertSame([
            'instantiator' => 'lazy',
            'lazy_unmarshal' => false,
            'hooks' => [
                'deserialize' => [
                    'object' => $hook,
                    'className' => $hook,
                ],
            ],
            'union_selector' => ['int|string' => 'int'],
            'json_decode_flags' => 123,
            'collect_errors' => true,
            'groups' => ['groupOne', 'groupTwo'],
            'constructor_option' => true,
        ], $context->toArray());
    }
}
