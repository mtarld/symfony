<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\PropertyValueFormatterOption;

final class PropertyValueFormatterNativeContextBuilder implements MarshalNativeContextBuilderInterface, GenerateNativeContextBuilderInterface
{
    public function buildMarshalNativeContext(string $type, Context $context, array $nativeContext): array
    {
        return $this->addPropertyValueFormatters($context, $nativeContext);
    }

    public function buildGenerateNativeContext(string $type, Context $context, array $nativeContext): array
    {
        return $this->addPropertyValueFormatters($context, $nativeContext);
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function addPropertyValueFormatters(Context $context, array $nativeContext): array
    {
        /** @var PropertyValueFormatterOption|null $valueFormatterOption */
        $valueFormatterOption = $context->get(PropertyValueFormatterOption::class);
        if (null === $valueFormatterOption) {
            return $nativeContext;
        }

        foreach ($valueFormatterOption->formatters as $formatterName => $formatter) {
            $nativeContext['symfony']['property_value_formatter'][$formatterName] = $formatter;
        }

        return $nativeContext;
    }
}
