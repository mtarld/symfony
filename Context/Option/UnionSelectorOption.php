<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\Option;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class UnionSelectorOption
{
    /**
     * @param array<string, string> $unionSelector
     */
    public function __construct(
        public readonly array $unionSelector,
    ) {
    }
}
