<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Tests\Serialize\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serialize\Context\SerializeContext;
use Symfony\Component\Serializer\Serialize\Hook\ObjectHookInterface;

class SerializeContextTest extends TestCase
{
    public function testWithers()
    {
        $hook = $this->createStub(ObjectHookInterface::class);

        $context = (new SerializeContext(['constructor_option' => true]))
            ->withGroups(['groupOne', 'groupTwo'])
            ->withForceGenerateTemplate()
            ->withType('TYPE')
            ->withJsonEncodeFlags(123)
            ->withHook($hook)
            ->withHook($hook, 'className');

        $this->assertSame([
            'hooks' => [
                'serialize' => [
                    'object' => $hook,
                    'className' => $hook,
                ],
            ],
            'json_encode_flags' => 123,
            'type' => 'TYPE',
            'force_generate_template' => true,
            'groups' => ['groupOne', 'groupTwo'],
            'constructor_option' => true,
        ], $context->toArray());
    }
}
