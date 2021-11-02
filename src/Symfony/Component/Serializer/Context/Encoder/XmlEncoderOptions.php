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

final class XmlEncoderOptions
{
    /**
     * Whether the decoded result should be considered as a collection
     * or as a single element.
     */
    private ?bool $asCollection = null;

    /**
     * Node types to ignore while decoding.
     *
     * @see https://www.php.net/manual/en/dom.constants.php
     *
     * @var list<int>
     */
    private ?array $decoderIgnoredNodeTypes = null;

    /**
     * Node types to ignore while encoding.
     *
     * @see https://www.php.net/manual/en/dom.constants.php
     *
     * @var list<int>
     */
    private ?array $encoderIgnoredNodeTypes = null;

    /**
     * DOMDocument encoding.
     *
     * @see https://www.php.net/manual/en/class.domdocument.php#domdocument.props.encoding
     */
    private ?string $encoding = null;

    /**
     * Whether to encode with indentation and extra space.
     *
     * @see https://php.net/manual/en/class.domdocument.php#domdocument.props.formatoutput
     */
    private ?bool $formatOutput = null;

    /**
     * DOMDocument::loadXml options bitmask.
     *
     * @see https://www.php.net/manual/en/libxml.constants.php
     */
    private ?int $loadOptions = null;

    /**
     * Whether to keep empty nodes.
     */
    private ?bool $removeEmptyTags = null;

    /**
     * Name of the root node.
     */
    private ?string $rootNodeName = null;

    /**
     * Whether the document will be standalone.
     *
     * @see https://php.net/manual/en/class.domdocument.php#domdocument.props.xmlstandalone
     */
    private ?bool $standalone = null;

    /**
     * Whether casting numeric string attributes to integers or floats.
     */
    private ?bool $typeCastAttributes = null;

    /**
     * Version number of the document.
     *
     * @see https://php.net/manual/en/class.domdocument.php#domdocument.props.xmlversion
     */
    private ?string $version = null;

    public function isAsCollection(): bool
    {
        return $this->asCollection ?? false;
    }

    public function setAsCollection(?bool $asCollection): self
    {
        $this->asCollection = $asCollection;

        return $this;
    }

    public function getDecoderIgnoredNodeTypes(): array
    {
        return $this->decoderIgnoredNodeTypes ?? [\XML_PI_NODE, \XML_COMMENT_NODE];
    }

    public function setDecoderIgnoredNodeTypes(?array $decoderIgnoredNodeTypes): self
    {
        $this->decoderIgnoredNodeTypes = $decoderIgnoredNodeTypes;

        return $this;
    }

    public function getEncoderIgnoredNodeTypes(): array
    {
        return $this->encoderIgnoredNodeTypes ?? [];
    }

    public function setEncoderIgnoredNodeTypes(?array $encoderIgnoredNodeTypes): self
    {
        $this->encoderIgnoredNodeTypes = $encoderIgnoredNodeTypes;

        return $this;
    }

    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    public function setEncoding(?string $encoding): self
    {
        $this->encoding = $encoding;

        return $this;
    }

    public function isFormatOutput(): ?bool
    {
        return $this->formatOutput;
    }

    public function setFormatOutput(?bool $formatOutput): self
    {
        $this->formatOutput = $formatOutput;

        return $this;
    }

    public function getLoadOptions(): int
    {
        return $this->loadOptions ?? \LIBXML_NONET | \LIBXML_NOBLANKS;
    }

    public function setLoadOptions(?int $loadOptions): self
    {
        $this->loadOptions = $loadOptions;

        return $this;
    }

    public function isRemoveEmptyTags(): bool
    {
        return $this->removeEmptyTags ?? false;
    }

    public function setRemoveEmptyTags(?bool $removeEmptyTags): self
    {
        $this->removeEmptyTags = $removeEmptyTags;

        return $this;
    }

    public function getRootNodeName(): string
    {
        return $this->rootNodeName ?? 'response';
    }

    public function setRootNodeName(?string $rootNodeName): self
    {
        $this->rootNodeName = $rootNodeName;

        return $this;
    }

    public function isStandalone(): ?bool
    {
        return $this->standalone;
    }

    public function setStandalone(?bool $standalone): self
    {
        $this->standalone = $standalone;

        return $this;
    }

    public function isTypeCastAttributes(): bool
    {
        return $this->typeCastAttributes ?? true;
    }

    public function setTypeCastAttributes(?bool $typeCastAttributes): self
    {
        $this->typeCastAttributes = $typeCastAttributes;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(?string $version): self
    {
        $this->version = $version;

        return $this;
    }


    public function merge(self $other): self
    {
        $this->asCollection ??= $other->asCollection;
        $this->decoderIgnoredNodeTypes ??= $other->decoderIgnoredNodeTypes;
        $this->encoderIgnoredNodeTypes ??= $other->encoderIgnoredNodeTypes;
        $this->encoding ??= $other->encoding;
        $this->formatOutput ??= $other->formatOutput;
        $this->loadOptions ??= $other->loadOptions;
        $this->removeEmptyTags ??= $other->removeEmptyTags;
        $this->rootNodeName ??= $other->rootNodeName;
        $this->standalone ??= $other->standalone;
        $this->typeCastAttributes ??= $other->typeCastAttributes;
        $this->version ??= $other->version;

        return $this;
    }

    /**
     * @internal
     *
     * @param array<string, mixed> $legacyContext
     */
    public static function fromLegacyContext(array $legacyContext = []): self
    {
        return (new self())
            ->setAsCollection($legacyContext['as_collection'] ?? null)
            ->setDecoderIgnoredNodeTypes($legacyContext['decoder_ignored_node_types'] ?? null)
            ->setEncoderIgnoredNodeTypes($legacyContext['encoder_ignored_node_types'] ?? null)
            ->setEncoding($legacyContext['xml_encoding'] ?? null)
            ->setFormatOutput($legacyContext['xml_format_output'] ?? null)
            ->setLoadOptions($legacyContext['load_options'] ?? null)
            ->setRemoveEmptyTags($legacyContext['remove_empty_tags'] ?? null)
            ->setRootNodeName($legacyContext['xml_root_node_name'] ?? null)
            ->setStandalone($legacyContext['xml_standalone'] ?? null)
            ->setTypeCastAttributes($legacyContext['xml_type_cast_attributes'] ?? null)
            ->setVersion($legacyContext['xml_version'] ?? null)
        ;
    }
}
