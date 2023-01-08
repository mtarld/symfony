<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder\Unmarshal;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\UnmarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\CollectErrorsOption;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class CollectErrorsContextBuilder implements UnmarshalContextBuilderInterface
{
    public function build(string $type, Context $context, array $rawContext): array
    {
        /** @var CollectErrorsOption|null $collectErrorsOption */
        $collectErrorsOption = $context->get(CollectErrorsOption::class);
        if (null === $collectErrorsOption) {
            return $rawContext;
        }

        $rawContext['collect_errors'] = $collectErrorsOption->collectErrors;

        return $rawContext;
    }
}
