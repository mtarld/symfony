<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\UnmarshalContext;

final class UnmarshalContextTest extends TestCase
{
    public function testWithers(): void
    {
        $hook = static function () {};

        $context = (new UnmarshalContext(['constructor_option' => true, 'hooks' => ['unmarshal' => ['constructor_hook' => $hook]]]))
            ->withCollectErrors()
            ->withJsonDecodeFlags(123)
            ->withUnionSelector(['int|string' => 'int'])
            ->withObjectHook($hook)
            ->withObjectHook($hook, 'className')
            ->withPropertyHook($hook)
            ->withPropertyHook($hook, 'className', 'propertyName')
            ->withHook('other_hook', $hook)
            ->withEagerReading()
            ->withLazyInstantiation();

        $this->assertSame([
            'instantiator' => 'lazy',
            'lazy_reading' => false,
            'hooks' => [
                'unmarshal' => [
                    'constructor_hook' => $hook,
                    'object' => $hook,
                    'className' => $hook,
                    'property' => $hook,
                    'className::$propertyName' => $hook,
                    'other_hook' => $hook,
                ],
            ],
            'union_selector' => ['int|string' => 'int'],
            'json_decode_flags' => 123,
            'collect_errors' => true,
            'constructor_option' => true,
        ], $context->toArray());
    }
}
