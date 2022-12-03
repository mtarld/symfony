<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Context\Option;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Option\HookOption;

final class HookOptionTest extends TestCase
{
    public function testCannotCreateWithInvalidFormatter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Hook "hook" of attribute "%s" is an invalid callable.', HookOption::class));

        new HookOption(['hook' => true]);
    }
}
