<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder\Marshal;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\MarshalContextBuilderInterface;
use Symfony\Component\Marshaller\Context\Option\TypeOption;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class TypeContextBuilder implements MarshalContextBuilderInterface
{
    public function build(Context $context, array $rawContext): array
    {
        /** @var TypeOption|null $typeOption */
        $typeOption = $context->get(TypeOption::class);
        if (null === $typeOption) {
            return $rawContext;
        }

        $rawContext['type'] = $typeOption->type;

        return $rawContext;
    }
}
