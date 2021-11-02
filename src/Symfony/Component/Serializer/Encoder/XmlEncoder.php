<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Encoder;

use Symfony\Component\Serializer\Context\Context;
use Symfony\Component\Serializer\Context\Encoder\XmlEncoderOptions;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Encodes XML data.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author John Wards <jwards@whiteoctober.co.uk>
 * @author Fabian Vogler <fabian@equivalence.ch>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Dany Maillard <danymaillard93b@gmail.com>
 */
class XmlEncoder implements EncoderInterface, DecoderInterface, NormalizationAwareInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public const FORMAT = 'xml';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const AS_COLLECTION = 'as_collection';

    /**
     * An array of ignored XML node types while decoding, each one of the DOM Predefined XML_* constants.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const DECODER_IGNORED_NODE_TYPES = 'decoder_ignored_node_types';

    /**
     * An array of ignored XML node types while encoding, each one of the DOM Predefined XML_* constants.
     *
     * @deprecated since symfony/serializer 6.1, use Context instead
     */
    public const ENCODER_IGNORED_NODE_TYPES = 'encoder_ignored_node_types';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const ENCODING = 'xml_encoding';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const FORMAT_OUTPUT = 'xml_format_output';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const LOAD_OPTIONS = 'load_options';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const REMOVE_EMPTY_TAGS = 'remove_empty_tags';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const ROOT_NODE_NAME = 'xml_root_node_name';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const STANDALONE = 'xml_standalone';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const TYPE_CAST_ATTRIBUTES = 'xml_type_cast_attributes';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const VERSION = 'xml_version';

    /**
     * @var \DOMDocument
     */
    private $dom;
    private $format;
    private $context;

    private ?XmlEncoderOptions $defaultOptions = null;

    private ?array $defaultLegacyContext = null;

    /**
     * @param Context|null $defaultContext
     */
    public function __construct(/* Context $defaultContext = null */)
    {
        /** @var Context|array|null $defaultContext */
        $defaultContext = 0 < \func_num_args() ? \func_get_arg(0) : null;
        if (\is_array($defaultContext)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $defaultContext is deprecated.');
            $this->defaultLegacyContext = array_merge((new XmlEncoderOptions())->toLegacyContext(), $defaultContext);

            return;
        }

        $this->defaultOptions = $defaultContext?->getOptions(XmlEncoderOptions::class) ?? new XmlEncoderOptions();
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     */
    public function encode(mixed $data, string $format /*, Context $context = null */): string
    {
        $context = $this->getContext(2 < \func_num_args() ? \func_get_arg(2) : null);

        $ignorePiNode = \in_array(\XML_PI_NODE, $context['encoder_ignored_node_types'], true);
        if ($data instanceof \DOMDocument) {
            return $data->saveXML($ignorePiNode ? $data->documentElement : null);
        }

        $this->dom = $this->createDomDocument($context);
        $this->format = $format;
        $this->context = $context;

        if (null !== $data && !is_scalar($data)) {
            $root = $this->dom->createElement($context['xml_root_node_name']);
            $this->dom->appendChild($root);
            $this->buildXml($root, $data, $context['xml_root_node_name']);
        } else {
            $this->appendNode($this->dom, $data, $context['xml_root_node_name']);
        }

        return $this->dom->saveXML($ignorePiNode ? $this->dom->documentElement : null);
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     */
    public function decode(string $data, string $format /*, Context $context = null */): mixed
    {
        $context = $this->getContext(2 < \func_num_args() ? \func_get_arg(2) : null);

        if ('' === trim($data)) {
            throw new NotEncodableValueException('Invalid XML data, it cannot be empty.');
        }

        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->loadXML($data, $context['load_options']);

        libxml_use_internal_errors($internalErrors);

        if ($error = libxml_get_last_error()) {
            libxml_clear_errors();

            throw new NotEncodableValueException($error->message);
        }

        $rootNode = null;
        foreach ($dom->childNodes as $child) {
            if (\XML_DOCUMENT_TYPE_NODE === $child->nodeType) {
                throw new NotEncodableValueException('Document types are not allowed.');
            }
            if (!$rootNode && !\in_array($child->nodeType, $context['decoder_ignored_node_types'], true)) {
                $rootNode = $child;
            }
        }

        // todo: throw an exception if the root node name is not correctly configured (bc)

        if ($rootNode->hasChildNodes()) {
            $xpath = new \DOMXPath($dom);
            $data = [];
            foreach ($xpath->query('namespace::*', $dom->documentElement) as $nsNode) {
                $data['@'.$nsNode->nodeName] = $nsNode->nodeValue;
            }

            unset($data['@xmlns:xml']);

            if (empty($data)) {
                return $this->parseXml($rootNode, $context);
            }

            return array_merge($data, (array) $this->parseXml($rootNode, $context));
        }

        if (!$rootNode->hasAttributes()) {
            return $rootNode->nodeValue;
        }

        $data = [];

        foreach ($rootNode->attributes as $attrKey => $attr) {
            $data['@'.$attrKey] = $attr->nodeValue;
        }

        $data['#'] = $rootNode->nodeValue;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }

    final protected function appendXMLString(\DOMNode $node, string $val): bool
    {
        if ('' !== $val) {
            $frag = $this->dom->createDocumentFragment();
            $frag->appendXML($val);
            $node->appendChild($frag);

            return true;
        }

        return false;
    }

    final protected function appendText(\DOMNode $node, string $val): bool
    {
        $nodeText = $this->dom->createTextNode($val);
        $node->appendChild($nodeText);

        return true;
    }

    final protected function appendCData(\DOMNode $node, string $val): bool
    {
        $nodeText = $this->dom->createCDATASection($val);
        $node->appendChild($nodeText);

        return true;
    }

    final protected function appendDocumentFragment(\DOMNode $node, \DOMDocumentFragment $fragment): bool
    {
        if ($fragment instanceof \DOMDocumentFragment) {
            $node->appendChild($fragment);

            return true;
        }

        return false;
    }

    final protected function appendComment(\DOMNode $node, string $data): bool
    {
        $node->appendChild($this->dom->createComment($data));

        return true;
    }

    /**
     * Checks the name is a valid xml element name.
     */
    final protected function isElementNameValid(string $name): bool
    {
        return $name &&
            !str_contains($name, ' ') &&
            preg_match('#^[\pL_][\pL0-9._:-]*$#ui', $name);
    }

    /**
     * Parse the input DOMNode into an array or a string.
     */
    private function parseXml(\DOMNode $node, array $context = []): array|string
    {
        $data = $this->parseXmlAttributes($node, $context);

        $value = $this->parseXmlValue($node, $context);

        if (!\count($data)) {
            return $value;
        }

        if (!\is_array($value)) {
            $data['#'] = $value;

            return $data;
        }

        if (1 === \count($value) && key($value)) {
            $data[key($value)] = current($value);

            return $data;
        }

        foreach ($value as $key => $val) {
            $data[$key] = $val;
        }

        return $data;
    }

    /**
     * Parse the input DOMNode attributes into an array.
     */
    private function parseXmlAttributes(\DOMNode $node, array $context = []): array
    {
        if (!$node->hasAttributes()) {
            return [];
        }

        $data = [];
        foreach ($node->attributes as $attr) {
            if (!is_numeric($attr->nodeValue) || !$context['xml_type_cast_attributes'] || (isset($attr->nodeValue[1]) && '0' === $attr->nodeValue[0] && '.' !== $attr->nodeValue[1])) {
                $data['@'.$attr->nodeName] = $attr->nodeValue;

                continue;
            }

            if (false !== $val = filter_var($attr->nodeValue, \FILTER_VALIDATE_INT)) {
                $data['@'.$attr->nodeName] = $val;

                continue;
            }

            $data['@'.$attr->nodeName] = (float) $attr->nodeValue;
        }

        return $data;
    }

    /**
     * Parse the input DOMNode value (content and children) into an array or a string.
     */
    private function parseXmlValue(\DOMNode $node, array $context = []): array|string
    {
        if (!$node->hasChildNodes()) {
            return $node->nodeValue;
        }

        if (1 === $node->childNodes->length && \in_array($node->firstChild->nodeType, [\XML_TEXT_NODE, \XML_CDATA_SECTION_NODE])) {
            return $node->firstChild->nodeValue;
        }

        $value = [];
        foreach ($node->childNodes as $subnode) {
            if (\in_array($subnode->nodeType, $context['decoder_ignored_node_types'], true)) {
                continue;
            }

            $val = $this->parseXml($subnode, $context);

            if ('item' === $subnode->nodeName && isset($val['@key'])) {
                $value[$val['@key']] = $val['#'] ?? $val;
            } else {
                $value[$subnode->nodeName][] = $val;
            }
        }

        foreach ($value as $key => $val) {
            if (!$context['as_collection'] && \is_array($val) && 1 === \count($val)) {
                $value[$key] = current($val);
            }
        }

        return $value;
    }

    /**
     * Parse the data and convert it to DOMElements.
     *
     * @throws NotEncodableValueException
     */
    private function buildXml(\DOMNode $parentNode, mixed $data, string $xmlRootNodeName = null): bool
    {
        $append = true;

        if (\is_array($data) || ($data instanceof \Traversable && (null === $this->serializer || !$this->serializer->supportsNormalization($data, $this->format)))) {
            foreach ($data as $key => $data) {
                //Ah this is the magic @ attribute types.
                if (str_starts_with($key, '@') && $this->isElementNameValid($attributeName = substr($key, 1))) {
                    if (!is_scalar($data)) {
                        $data = $this->serializer->normalize($data, $this->format, $this->context);
                    }
                    $parentNode->setAttribute($attributeName, $data);
                } elseif ('#' === $key) {
                    $append = $this->selectNodeType($parentNode, $data);
                } elseif ('#comment' === $key) {
                    if (!\in_array(\XML_COMMENT_NODE, $this->context['encoder_ignored_node_types'], true)) {
                        $append = $this->appendComment($parentNode, $data);
                    }
                } elseif (\is_array($data) && false === is_numeric($key)) {
                    // Is this array fully numeric keys?
                    if (ctype_digit(implode('', array_keys($data)))) {
                        /*
                         * Create nodes to append to $parentNode based on the $key of this array
                         * Produces <xml><item>0</item><item>1</item></xml>
                         * From ["item" => [0,1]];.
                         */
                        foreach ($data as $subData) {
                            $append = $this->appendNode($parentNode, $subData, $key);
                        }
                    } else {
                        $append = $this->appendNode($parentNode, $data, $key);
                    }
                } elseif (is_numeric($key) || !$this->isElementNameValid($key)) {
                    $append = $this->appendNode($parentNode, $data, 'item', $key);
                } elseif (null !== $data || !$this->context['remove_empty_tags']) {
                    $append = $this->appendNode($parentNode, $data, $key);
                }
            }

            return $append;
        }

        if (\is_object($data)) {
            if (null === $this->serializer) {
                throw new BadMethodCallException(sprintf('The serializer needs to be set to allow "%s()" to be used with object data.', __METHOD__));
            }

            $data = $this->serializer->normalize($data, $this->format, $this->context);
            if (null !== $data && !is_scalar($data)) {
                return $this->buildXml($parentNode, $data, $xmlRootNodeName);
            }

            // top level data object was normalized into a scalar
            if (!$parentNode->parentNode->parentNode) {
                $root = $parentNode->parentNode;
                $root->removeChild($parentNode);

                return $this->appendNode($root, $data, $xmlRootNodeName);
            }

            return $this->appendNode($parentNode, $data, 'data');
        }

        throw new NotEncodableValueException('An unexpected value could not be serialized: '.(!\is_resource($data) ? var_export($data, true) : sprintf('%s resource', get_resource_type($data))));
    }

    /**
     * Selects the type of node to create and appends it to the parent.
     */
    private function appendNode(\DOMNode $parentNode, mixed $data, string $nodeName, string $key = null): bool
    {
        $node = $this->dom->createElement($nodeName);
        if (null !== $key) {
            $node->setAttribute('key', $key);
        }
        $appendNode = $this->selectNodeType($node, $data);
        // we may have decided not to append this node, either in error or if its $nodeName is not valid
        if ($appendNode) {
            $parentNode->appendChild($node);
        }

        return $appendNode;
    }

    /**
     * Checks if a value contains any characters which would require CDATA wrapping.
     */
    private function needsCdataWrapping(string $val): bool
    {
        return 0 < preg_match('/[<>&]/', $val);
    }

    /**
     * Tests the value being passed and decide what sort of element to create.
     *
     * @throws NotEncodableValueException
     */
    private function selectNodeType(\DOMNode $node, mixed $val): bool
    {
        if (\is_array($val)) {
            return $this->buildXml($node, $val);
        } elseif ($val instanceof \SimpleXMLElement) {
            $child = $this->dom->importNode(dom_import_simplexml($val), true);
            $node->appendChild($child);
        } elseif ($val instanceof \Traversable) {
            $this->buildXml($node, $val);
        } elseif ($val instanceof \DOMNode) {
            $child = $this->dom->importNode($val, true);
            $node->appendChild($child);
        } elseif (\is_object($val)) {
            if (null === $this->serializer) {
                throw new BadMethodCallException(sprintf('The serializer needs to be set to allow "%s()" to be used with object data.', __METHOD__));
            }

            return $this->selectNodeType($node, $this->serializer->normalize($val, $this->format, $this->context));
        } elseif (is_numeric($val)) {
            return $this->appendText($node, (string) $val);
        } elseif (\is_string($val) && $this->needsCdataWrapping($val)) {
            return $this->appendCData($node, $val);
        } elseif (\is_string($val)) {
            return $this->appendText($node, $val);
        } elseif (\is_bool($val)) {
            return $this->appendText($node, (int) $val);
        }

        return true;
    }

    /**
     * Create a DOM document, taking serializer options into account.
     */
    private function createDomDocument(array $context): \DOMDocument
    {
        $document = new \DOMDocument();

        // Set an attribute on the DOM document specifying, as part of the XML declaration,
        $xmlOptions = [
            'formatOutput' => $context['xml_format_output'],
            'xmlVersion' => $context['xml_version'],
            'encoding' => $context['xml_encoding'],
            'xmlStandalone' => $context['xml_standalone'],
        ];

        foreach ($xmlOptions as $documentProperty => $value) {
            if (null !== $value) {
                $document->$documentProperty = $value;
            }
        }

        return $document;
    }

    private function getOptions(?Context $context): XmlEncoderOptions
    {
        $options = $context?->getOptions(XmlEncoderOptions::class);

        return null !== $options ? $options->merge($this->defaultOptions) : $this->defaultOptions;
    }

    /**
     * Prepare a context array filled with defaults based
     * on either a Context object or the legacy context array.
     *
     * Used for BC layer.
     *
     * @param Context|array<string, mixed>|null $context
     *
     * @return array<string, mixed>
     */
    private function getContext(Context|array|null $context): array
    {
        $defaultLegacyContext = null !== $this->defaultOptions ? $this->defaultOptions->toLegacyContext() : $this->defaultLegacyContext;

        if (null === $context) {
            return $defaultLegacyContext;
        }

        if (\is_array($context)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $context is deprecated.');

            return [
                'as_collection' => $context['as_collection'] ?? $defaultLegacyContext['as_collection'],
                'decoder_ignored_node_types' => $context['decoder_ignored_node_types'] ?? $defaultLegacyContext['decoder_ignored_node_types'],
                'encoder_ignored_node_types' => $context['encoder_ignored_node_types'] ?? $defaultLegacyContext['encoder_ignored_node_types'],
                'xml_encoding' => $context['xml_encoding'] ?? $defaultLegacyContext['xml_encoding'],
                'xml_format_output' => $context['xml_format_output'] ?? $defaultLegacyContext['xml_format_output'],
                'load_options' => $context['load_options'] ?? $defaultLegacyContext['load_options'],
                'remove_empty_tags' => $context['remove_empty_tags'] ?? $defaultLegacyContext['remove_empty_tags'],
                'xml_root_node_name' => $context['xml_root_node_name'] ?? $defaultLegacyContext['xml_root_node_name'],
                'xml_standalone' => $context['xml_standalone'] ?? $defaultLegacyContext['xml_standalone'],
                'xml_type_cast_attributes' => $context['xml_type_cast_attributes'] ?? $defaultLegacyContext['xml_type_cast_attributes'],
                'xml_version' => $context['xml_version'] ?? $defaultLegacyContext['xml_version'],
            ];
        }

        return $this->getOptions($context)->toLegacyContext();
    }
}
