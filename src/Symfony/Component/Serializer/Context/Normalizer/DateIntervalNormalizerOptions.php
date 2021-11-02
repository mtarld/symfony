<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Context\Normalizer;

use DateTimeZone;

final class DateIntervalNormalizerOptions
{
    /**
     * TODO
     */
    private ?string $format = null;

    /**
     * TODO
     */
    private ?\DateTimeZone $timezone = null;

    public function getFormat(): string
    {
        return $this->format ?? '%rP%yY%mM%dDT%hH%iM%sS';
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function merge(self $other): self
    {
        $this->format ??= $other->format;

        return $this;
    }

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    public function toLegacyContext(): array
    {
        return [
            'dateinterval_format' => $this->getFormat(),
        ];
    }
}
