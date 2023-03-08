<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context;

trait ContextTrait
{
    public function toArray(): array
    {
        return $this->options;
    }

    protected function with(string $key, mixed $value): static
    {
        return new static([$key => $value] + $this->options);
    }
}
