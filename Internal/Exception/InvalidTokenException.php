<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Internal\Exception;

final class InvalidTokenException extends \InvalidArgumentException
{
    public function __construct(string $expected, string $actual)
    {
        parent::__construct(sprintf('Expected "%s" token, got "%s".', $expected, $actual));
    }
}
