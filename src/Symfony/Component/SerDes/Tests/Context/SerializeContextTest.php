<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Tests\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Component\SerDes\Context\SerializeContext;

class SerializeContextTest extends TestCase
{
    public function testWithers()
    {
        $hook = static function () {};

        $context = (new SerializeContext(['constructor_option' => true, 'hooks' => ['serialize' => ['constructor_hook' => $hook]]]))
            ->withForceGenerateTemplate()
            ->withType('TYPE')
            ->withJsonEncodeFlags(123)
            ->withUnionSelector(['int|string' => 'int'])
            ->withObjectHook($hook)
            ->withObjectHook($hook, 'className')
            ->withPropertyHook($hook)
            ->withPropertyHook($hook, 'className', 'propertyName');

        $this->assertSame([
            'hooks' => [
                'serialize' => [
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
            'force_generate_template' => true,
            'constructor_option' => true,
        ], $context->toArray());
    }
}
