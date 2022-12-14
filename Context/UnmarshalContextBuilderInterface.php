<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

interface UnmarshalContextBuilderInterface
{
    /**
     * @param array<string, mixed> $rawContext
     *
     * @return array<string, mixed>
     */
    public function build(string $type, Context $context, array $rawContext): array;
}
