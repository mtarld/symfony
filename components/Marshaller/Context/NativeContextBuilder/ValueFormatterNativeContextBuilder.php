<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\ValueFormattersOption;
use Symfony\Component\Marshaller\NativeContext\NativeContextBuilderInterface;

final class ValueFormatterNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        /** @var ValueFormattersOption|null $valueFormattersOption */
        $valueFormattersOption = $context->get(ValueFormattersOption::class);
        if (null === $valueFormattersOption) {
            return $nativeContext;
        }

        foreach ($valueFormattersOption->formatters as $formatterName => $formatter) {
            $nativeContext['value_formatters'][$formatterName] = $formatter;
        }

        return $nativeContext;
    }
}
