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

final class DateTimeNormalizerOptions
{
    /**
     * TODO
     */
    private ?string $format = null;

    /**
     * TODO
     */
    private ?\DateTimeZone $timezone = null;

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getTimezone(): ?DateTimeZone
    {
        return $this->timezone;
    }

    public function setTimezone(\DateTimeZone|string|null $timezone): self
    {
        if (is_string($timezone)) {
            $timezone = new \DateTimeZone($timezone);
        }

        $this->timezone = $timezone;

        return $this;
    }

    public function merge(self $other): self
    {
        $this->format ??= $other->format;
        $this->timezone ??= $other->timezone;

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
            'datetime_format' => $this->getFormat(),
            'datetime_timezone' => $this->getTimezone(),
        ];
    }
}
