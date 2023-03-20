<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\MarshalContext;

class MarshalContextTest extends TestCase
{
    public function testWithers()
    {
        $hook = static function () {};

        $context = (new MarshalContext(['constructor_option' => true, 'hooks' => ['marshal' => ['constructor_hook' => $hook]]]))
            ->withType('TYPE')
            ->withJsonEncodeFlags(123)
            ->withUnionSelector(['int|string' => 'int'])
            ->withObjectHook($hook)
            ->withObjectHook($hook, 'className')
            ->withPropertyHook($hook)
            ->withPropertyHook($hook, 'className', 'propertyName');

        $this->assertSame([
            'hooks' => [
                'marshal' => [
                    'constructor_hook' => $hook,
                    'object' => $hook,
                    'className' => $hook,
                    'property' => $hook,
                    'className::$propertyName' => $hook,
                ],
            ],
            'union_selector' => ['int|string' => 'int'],
            'json_encode_flags' => 123,
            'type' => 'TYPE',
            'constructor_option' => true,
        ], $context->toArray());
    }
}
