<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Context;

final class ContextTest extends TestCase
{
    public function testAddAndRemoveOptions(): void
    {
        $context = new Context(new FooOption(), new BarOption());
        $this->assertSame([FooOption::class, BarOption::class], $this->optionClasses($context));

        $context = $context->without(FooOption::class, BarOption::class);
        $this->assertSame([], $this->optionClasses($context));

        $context = $context->with(new FooOption(), new BarOption());
        $this->assertSame([FooOption::class, BarOption::class], $this->optionClasses($context));
    }

    public function testGetOption(): void
    {
        $context = new Context($option = new FooOption());
        $this->assertSame($option, $context->get(FooOption::class));
    }

    public function testIterateOptions(): void
    {
        $context = new Context($fooOption = new FooOption(), $barOption = new BarOption());
        $options = [$fooOption, $barOption];

        foreach ($context as $i => $option) {
            $this->assertSame($options[$i], $option);
        }
    }

    /**
     * @return list<class-string>
     */
    private function optionClasses(Context $context): array
    {
        return array_map(fn ($o) => $o::class, iterator_to_array($context));
    }
}

final class FooOption
{
}

final class BarOption
{
}
