<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\PropertyValueFormatterOption;

final class PropertyValueFormatterNativeContextBuilder implements NativeContextBuilderInterface
{
    // TODO be able to handle native type hook as well
    public function build(string $type, string $format, Context $context, array $nativeContext): array
    {
        /** @var PropertyValueFormatterOption|null $valueFormatterOption */
        $valueFormatterOption = $context->get(PropertyValueFormatterOption::class);
        if (null === $valueFormatterOption) {
            return $nativeContext;
        }

        foreach ($valueFormatterOption->formatters as $formatterName => $formatter) {
            $nativeContext['symfony']['property_value_formatter'][$formatterName] = $formatter;
        }

        if ([] !== $valueFormatterOption->formatters) {
            $nativeContext['symfony']['formatter_type_extractor'] = static function (): string {
            };
        }

        return $nativeContext;
    }
}
