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
use Symfony\Component\Marshaller\Context\Option\InstantiatorOption;
use Symfony\Component\Marshaller\Instantiator\InstantiatorInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class InstantiatorContextBuilder implements UnmarshalContextBuilderInterface
{
    public function __construct(
        private readonly InstantiatorInterface $lazyObjectInstantiator,
    ) {
    }

    public function build(string $type, Context $context, array $rawContext): array
    {
        /** @var InstantiatorOption|null $instantiatorOption */
        $instantiatorOption = $context->get(InstantiatorOption::class);
        if (null === $instantiatorOption) {
            $rawContext['instantiator'] = ($this->lazyObjectInstantiator)(...);

            return $rawContext;
        }

        $rawContext['instantiator'] = match ($instantiatorOption->instantiator) {
            InstantiatorOption::LAZY => ($this->lazyObjectInstantiator)(...),
            InstantiatorOption::EAGER => null,
            default => $instantiatorOption->instantiator,
        };

        return $rawContext;
    }
}
