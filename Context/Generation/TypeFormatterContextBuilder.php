<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context\Generation;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\GenerationContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;

final class TypeFormatterContextBuilder implements GenerationContextBuilderInterface
{
    public function build(string $type, Context $context, array $rawContext): array
    {
        /** @var TypeFormatterOption|null $typeFormatterOption */
        $typeFormatterOption = $context->get(TypeFormatterOption::class);
        if (null === $typeFormatterOption) {
            return $rawContext;
        }

        foreach ($typeFormatterOption->formatters as $formatterName => $formatter) {
            $rawContext['symfony']['marshal']['type_formatter'][$formatterName] = $formatter;
        }

        return $rawContext;
    }
}
