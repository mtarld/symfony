<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Context\Option;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Context\Option\TypeValueFormatterOption;

final class TypeValueFormatterOptionTest extends TestCase
{
    public function testCannotCreateWithInvalidFormatter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Formatter "type" of attribute "%s" is an invalid callable.', TypeValueFormatterOption::class));

        new TypeValueFormatterOption(['type' => true]);
    }
}
