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
use Symfony\Component\Marshaller\Context\Option\UnmarshalModeOption;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class ModeContextBuilder implements UnmarshalContextBuilderInterface
{
    public function build(string $type, Context $context, array $rawContext): array
    {
        /** @var UnmarshalModeOption|null $unmarshalModeOption */
        $unmarshalModeOption = $context->get(UnmarshalModeOption::class);
        if (null === $unmarshalModeOption) {
            return $rawContext;
        }

        $rawContext['mode'] = $unmarshalModeOption->mode;

        return $rawContext;
    }
}
