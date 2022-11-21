<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\PropertyTypeOption;

final class PropertyTypeNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        /** @var PropertyTypeOption|null $typeOption */
        $typeOption = $context->get(PropertyTypeOption::class);
        if (null === $typeOption) {
            return $nativeContext;
        }

        foreach ($typeOption->types as $propertyName => $type) {
            $nativeContext['symfony']['property_type'][$propertyName] = $type;
        }

        return $nativeContext;
    }
}
