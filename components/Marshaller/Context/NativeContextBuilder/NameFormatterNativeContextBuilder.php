<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\NativeContextBuilder;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\NameFormattersOption;

final class NameFormatterNativeContextBuilder implements NativeContextBuilderInterface
{
    public function build(string $format, Context $context, array $nativeContext): array
    {
        /** @var NameFormattersOption|null $nameFormattersOption */
        $nameFormattersOption = $context->get(NameFormattersOption::class);
        if (null === $nameFormattersOption) {
            return $nativeContext;
        }

        foreach ($nameFormattersOption->formatters as $formatterName => $formatter) {
            $nativeContext['name_formatters'][$formatterName] = $formatter;
        }

        return $nativeContext;
    }
}
