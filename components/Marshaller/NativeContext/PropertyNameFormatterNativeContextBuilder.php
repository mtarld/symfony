<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\PropertyNameFormatterOption;

final class PropertyNameFormatterNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        /** @var PropertyNameFormatterOption|null $nameFormatterOption */
        $nameFormatterOption = $context->get(PropertyNameFormatterOption::class);
        if (null === $nameFormatterOption) {
            return $nativeContext;
        }

        foreach ($nameFormatterOption->formatters as $formatterName => $formatter) {
            $nativeContext['symfony']['property_name_formatter'][$formatterName] = $formatter;
        }

        return $nativeContext;
    }
}
