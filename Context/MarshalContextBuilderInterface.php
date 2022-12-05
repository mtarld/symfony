<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

interface MarshalContextBuilderInterface
{
    /**
     * @param array<string, mixed> $rawContext
     *
     * @return array<string, mixed>
     */
    public function build(Context $context, array $rawContext): array;
}
