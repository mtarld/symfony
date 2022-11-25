<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;

final class TypeFormatterNativeContextBuilder implements GenerateNativeContextBuilderInterface
{
    public function buildGenerateNativeContext(string $type, Context $context, array $nativeContext): array
    {
        /** @var TypeFormatterOption|null $typeFormatterOption */
        $typeFormatterOption = $context->get(TypeFormatterOption::class);
        if (null === $typeFormatterOption) {
            return $nativeContext;
        }

        foreach ($typeFormatterOption->formatters as $formatterName => $formatter) {
            $nativeContext['symfony']['type_formatter'][$formatterName] = $formatter;
        }

        return $nativeContext;
    }
}
