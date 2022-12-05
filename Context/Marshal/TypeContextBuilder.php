<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Marshal;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\MarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\TypeOption;

final class TypeContextBuilder implements MarshalContextBuilderInterface
{
    public function build(Context $context, array $rawContext): array
    {
        /** @var TypeOption|null $typeOption */
        $typeOption = $context->get(TypeOption::class);
        if (null === $typeOption) {
            return $rawContext;
        }

        $rawContext['type'] = $typeOption->type;

        return $rawContext;
    }
}
