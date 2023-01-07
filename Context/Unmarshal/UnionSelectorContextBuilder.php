<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\Unmarshal;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\UnionSelectorOption;
use Symfony\Component\Marshaller\Context\UnmarshalContextBuilderInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class UnionSelectorContextBuilder implements UnmarshalContextBuilderInterface
{
    public function build(string $type, Context $context, array $rawContext): array
    {
        /** @var UnionSelectorOption|null $unionSelectorOption */
        $unionSelectorOption = $context->get(UnionSelectorOption::class);
        if (null === $unionSelectorOption) {
            return $rawContext;
        }

        $rawContext['union_selector'] = $unionSelectorOption->unionSelector;

        return $rawContext;
    }
}
