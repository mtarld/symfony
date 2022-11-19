<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeValueFormatterOption;

final class TypeValueFormatterNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        /** @var TypeValueFormatterOption|null $valueFormatterOption */
        $valueFormatterOption = $context->get(TypeValueFormatterOption::class);
        if (null === $valueFormatterOption) {
            return $nativeContext;
        }

        foreach ($valueFormatterOption->formatters as $formatterName => $formatter) {
            $nativeContext['symfony']['type_value_formatter'][$formatterName] = $formatter;
        }

        return $nativeContext;
    }
}
