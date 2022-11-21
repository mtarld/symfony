<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeValueFormatterOption;

final class TypeValueFormatterNativeContextBuilder implements MarshalNativeContextBuilderInterface, GenerateNativeContextBuilderInterface
{
    public function buildMarshalNativeContext(string $type, Context $context, array $nativeContext): array
    {
        return $this->addTypeValueFormatters($context, $nativeContext);
    }

    public function buildGenerateNativeContext(string $type, Context $context, array $nativeContext): array
    {
        return $this->addTypeValueFormatters($context, $nativeContext);
    }

    /**
     * @param array<string, mixed> $nativeContext
     *
     * @return array<string, mixed>
     */
    private function addTypeValueFormatters(Context $context, array $nativeContext): array
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
