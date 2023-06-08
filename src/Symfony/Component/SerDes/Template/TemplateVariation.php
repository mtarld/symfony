<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Template;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class TemplateVariation implements \Stringable
{
    public function __construct(
        public string $type,
        public string $value,
    ) {
    }

    public static function createGroup(string $group): self
    {
        return new self('group', $group);
    }

    public function compare(self $other): int
    {
        return (string) $this <=> (string) $other;
    }

    public function __toString(): string
    {
        return sprintf('%s-%s', $this->type, $this->value);
    }
}
