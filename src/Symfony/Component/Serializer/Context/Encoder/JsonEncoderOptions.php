<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Context\Encoder;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;

final class JsonEncoderOptions
{
    /**
     * json_encode flags bitmask.
     *
     * @see https://www.php.net/manual/en/json.constants.php
     */
    private ?int $encodeOptions = null;

    /**
     * json_decode flags bitmask.
     *
     * @see https://www.php.net/manual/en/json.constants.php
     */
    private ?int $decodeOptions = null;

    /**
     * Whether decoded objects will be given as
     * associative arrays or as nested stdClass.
     */
    private ?bool $associative = null;

    /**
     * Configures the maximum recursion depth.
     * Must be strictly positive.
     */
    private ?int $recursionDepth = null;

    public function getEncodeOptions(): int
    {
        return $this->encodeOptions ?? 0;
    }

    public function setEncodeOptions(?int $encodeOptions): self
    {
        $this->encodeOptions = $encodeOptions;

        return $this;
    }

    public function getDecodeOptions(): int
    {
        return $this->decodeOptions ?? 0;
    }

    public function setDecodeOptions(?int $decodeOptions): self
    {
        $this->decodeOptions = $decodeOptions;

        return $this;
    }

    public function isAssociative(): bool
    {
        return $this->associative ?? false;
    }

    public function setAssociative(?bool $associative): self
    {
        $this->associative = $associative;

        return $this;
    }

    public function getRecursionDepth(): int
    {
        return $this->recursionDepth ?? 512;
    }

    public function setRecursionDepth(?int $recursionDepth): self
    {
        if (null !== $recursionDepth && $recursionDepth <= 0) {
            throw new InvalidArgumentException(sprintf('The recursion depth must be strictly positive, "%d" given.', $recursionDepth));
        }

        $this->recursionDepth = $recursionDepth;

        return $this;
    }

    public function merge(self $other): self
    {
        $this->encodeOptions ??= $other->encodeOptions;
        $this->decodeOptions ??= $other->decodeOptions;
        $this->associative ??= $other->associative;
        $this->recursionDepth ??= $other->recursionDepth;

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
            'json_encode_options' => $this->getEncodeOptions(),
            'json_decode_options' => $this->getDecodeOptions(),
            'json_decode_associative' => $this->isAssociative(),
            'json_decode_recursion_depth' => $this->getRecursionDepth(),
        ];
    }
}
