<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Context;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
trait ContextBuilderTrait
{
    /**
     * @var array<string, mixed>
     */
    protected array $context;

    public function __construct(array $context = [])
    {
        $this->context = $context;
    }

    protected function with(string $key, mixed $value): static
    {
        return new static(array_merge($this->context, [$key => $value]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->context;
    }
}
