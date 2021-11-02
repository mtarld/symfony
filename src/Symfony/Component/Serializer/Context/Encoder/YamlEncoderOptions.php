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

final class YamlEncoderOptions
{
    /**
     * Threshold to switch to inline YAML.
     */
    private ?int $inlineThreshold = null;

    /**
     * Identation level.
     */
    private ?int $indentLevel = null;

    /**
     * \Symfony\Component\Yaml\Dumper::dump flags bitmask.
     *
     * @see \Symfony\Component\Yaml\Yaml
     */
    private ?int $flags = null;

    /**
     * Whether perserve empty objects "{}" or convert them to null
     */
    private ?bool $preserveEmptyObjects = null;

    public function getInlineThreshold(): int
    {
        return $this->inlineThreshold ?? 0;
    }

    public function setInlineThreshold(?int $inlineThreshold): self
    {
        $this->inlineThreshold = $inlineThreshold;

        return $this;
    }

    public function getIndentLevel(): int
    {
        return $this->indentLevel ?? 0;
    }

    public function setIndentLevel(?int $indentLevel): self
    {
        if (null !== $indentLevel && $indentLevel < 0) {
            throw new InvalidArgumentException(sprintf('The indent level must be positive, "%d" given.', $indentLevel));
        }

        $this->indentLevel = $indentLevel;

        return $this;
    }

    public function getFlags(): int
    {
        return $this->flags ?? 0;
    }

    public function setFlags(?int $flags): self
    {
        $this->flags = $flags;

        return $this;
    }

    public function isPreserveEmptyObjects(): ?bool
    {
        return $this->preserveEmptyObjects ?? false;
    }

    public function setPreserveEmptyObjects(?bool $preserveEmptyObjects): self
    {
        $this->preserveEmptyObjects = $preserveEmptyObjects;

        return $this;
    }

    public function merge(self $other): self
    {
        $this->inlineThreshold ??= $other->inlineThreshold;
        $this->indentLevel ??= $other->indentLevel;
        $this->flags ??= $other->flags;
        $this->preserveEmptyObjects ??= $other->preserveEmptyObjects;

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
            'yaml_inline' => $this->getInlineThreshold(),
            'yaml_indent' => $this->getIndentLevel(),
            'yaml_flags' => $this->getFlags(),
            'preserve_empty_objects' => $this->isPreserveEmptyObjects(),
        ];
    }
}
