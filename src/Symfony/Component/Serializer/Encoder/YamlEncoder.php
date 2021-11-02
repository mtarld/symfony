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
use Symfony\Component\Serializer\Context\Encoder\YamlEncoderOptions;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * Encodes YAML data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class YamlEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'yaml';
    private const ALTERNATIVE_FORMAT = 'yml';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const PRESERVE_EMPTY_OBJECTS = 'preserve_empty_objects';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const YAML_INLINE = 'yaml_inline';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const YAML_INDENT = 'yaml_indent';

    /** @deprecated since symfony/serializer 6.1, use Context instead */
    public const YAML_FLAGS = 'yaml_flags';

    private $dumper;
    private $parser;

    private YamlEncoderOptions $defaultOptions;

    /**
     * @param Context|null $defaultContext
     */
    public function __construct(Dumper $dumper = null, Parser $parser = null /*, Context $defaultContext = null */)
    {
        if (!class_exists(Dumper::class)) {
            throw new RuntimeException('The YamlEncoder class requires the "Yaml" component. Install "symfony/yaml" to use it.');
        }

        $this->dumper = $dumper ?? new Dumper();
        $this->parser = $parser ?? new Parser();

        /** @var Context|array|null $defaultContext */
        $defaultContext = 2 < \func_num_args() ? \func_get_arg(2) : null;
        if (\is_array($defaultContext)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $defaultContext is deprecated.');

            $defaultContext = new Context(YamlEncoderOptions::fromLegacyContext($defaultContext));
        }

        $this->defaultOptions = $defaultContext?->getOptions(YamlEncoderOptions::class) ?? new YamlEncoderOptions();
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     */
    public function encode(mixed $data, string $format /*, Context $context = null */): string
    {
        /** @var Context|array|null $context */
        $context = 2 < \func_num_args() ? \func_get_arg(2) : null;
        if (\is_array($context)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $context is deprecated.');

            $context = new Context(YamlEncoderOptions::fromLegacyContext($context));
        }

        $options = $this->getOptions($context);

        $flags = $options->getFlags();
        if ($options->getPreserveEmptyObjects()) {
            $flags |= Yaml::DUMP_OBJECT_AS_MAP;
        }

        return $this->dumper->dump($data, $options->getInlineThreshold(), $options->getIndentLevel(), $flags);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding(string $format): bool
    {
        return self::FORMAT === $format || self::ALTERNATIVE_FORMAT === $format;
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     */
    public function decode(string $data, string $format /*, Context $context = null */): mixed
    {
        /** @var Context|array|null $context */
        $context = 2 < \func_num_args() ? \func_get_arg(2) : null;
        if (\is_array($context)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $context is deprecated.');

            $context = new Context(YamlEncoderOptions::fromLegacyContext($context));
        }

        $options = $this->getOptions($context);

        return $this->parser->parse($data, $options->getFlags());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format || self::ALTERNATIVE_FORMAT === $format;
    }

    private function getOptions(?Context $context): YamlEncoderOptions
    {
        $options = $context?->getOptions(YamlEncoderOptions::class);

        return null !== $options ? $options->merge($this->defaultOptions) : $this->defaultOptions;
    }
}
