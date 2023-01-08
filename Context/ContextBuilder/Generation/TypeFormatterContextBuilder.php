<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder\Generation;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\GenerationContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\TypeFormatterOption;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
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
