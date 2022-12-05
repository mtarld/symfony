<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Marshal;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\MarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\JsonEncodeFlagsOption;

final class JsonEncodeFlagsContextBuilder implements MarshalContextBuilderInterface
{
    public function build(Context $context, array $rawContext): array
    {
        /** @var JsonEncodeFlagsOption|null $jsonEncodeFlagsOption */
        $jsonEncodeFlagsOption = $context->get(JsonEncodeFlagsOption::class);
        if (null === $jsonEncodeFlagsOption) {
            return $rawContext;
        }

        $rawContext['json_encode_flags'] = $jsonEncodeFlagsOption->flags;

        return $rawContext;
    }
}
