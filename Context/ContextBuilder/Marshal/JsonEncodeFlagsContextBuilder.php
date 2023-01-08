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
use Symfony\Component\Marshaller\Context\Option\JsonEncodeFlagsOption;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class JsonEncodeFlagsContextBuilder implements MarshalContextBuilderInterface
{
    public function build(Context $context, array $rawContext): array
    {
        /** @var JsonEncodeFlagsOption|null $jsonEncodeFlagsOption */
        $jsonEncodeFlagsOption = $context->get(JsonEncodeFlagsOption::class);
        if (null === $jsonEncodeFlagsOption) {
            return $rawContext;
        }

        $rawContext['json_encode_flags'] = $jsonEncodeFlagsOption->flags;

        return $rawContext;
    }
}
