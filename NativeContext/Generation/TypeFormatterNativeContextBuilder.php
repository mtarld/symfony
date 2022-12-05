<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\NativeContext\Generation;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;
use Symfony\Component\Marshaller\NativeContext\GenerationNativeContextBuilderInterface;

final class TypeFormatterNativeContextBuilder implements GenerationNativeContextBuilderInterface
{
    public function build(string $type, Context $context, array $nativeContext): array
    {
        /** @var TypeFormatterOption|null $typeFormatterOption */
        $typeFormatterOption = $context->get(TypeFormatterOption::class);
        if (null === $typeFormatterOption) {
            return $nativeContext;
        }

        foreach ($typeFormatterOption->formatters as $formatterName => $formatter) {
            $nativeContext['symfony']['marshal']['type_formatter'][$formatterName] = $formatter;
        }

        return $nativeContext;
    }
}
