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
use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * Encoder delegating the decoding to a chain of encoders.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * @final
 */
class ChainEncoder implements ContextAwareEncoderInterface
{
    private array $encoders = [];
    private array $encoderByFormat = [];

    public function __construct(array $encoders = [])
    {
        $this->encoders = $encoders;
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     */
    final public function encode(mixed $data, string $format /*, Context $context = null */): string
    {
        /** @var Context|array|null $context */
        $context = 2 < \func_num_args() ? \func_get_arg(2) : null;
        if (\is_array($context)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $context is deprecated.');
        }

        return $this->getEncoder($format, $context)->encode($data, $format, $context);
    }

    /**
     * {@inheritdoc}
     *
     * @param Context|null $context
     */
    public function supportsEncoding(string $format /*, Context $context = null */): bool
    {
        /** @var Context|array|null $context */
        $context = 1 < \func_num_args() ? \func_get_arg(1) : null;
        if (\is_array($context)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $context is deprecated.');
        }

        try {
            $this->getEncoder($format, $context);
        } catch (RuntimeException) {
            return false;
        }

        return true;
    }

    /**
     * Checks whether the normalization is needed for the given format.
     *
     * @param Context|null $context
     */
    public function needsNormalization(string $format /*, Context $context = null */): bool
    {
        /** @var Context|array|null $context */
        $context = 1 < \func_num_args() ? \func_get_arg(1) : null;
        if (\is_array($context)) {
            trigger_deprecation('symfony/serializer', '6.1', 'Passing an array for $context is deprecated.');
        }

        $encoder = $this->getEncoder($format, $context);

        if (!$encoder instanceof NormalizationAwareInterface) {
            return true;
        }

        if ($encoder instanceof self) {
            return $encoder->needsNormalization($format, $context);
        }

        return false;
    }

    /**
     * Gets the encoder supporting the format.
     *
     * @throws RuntimeException if no encoder is found
     */
    private function getEncoder(string $format, Context|array|null $context): EncoderInterface
    {
        if (isset($this->encoderByFormat[$format])
            && isset($this->encoders[$this->encoderByFormat[$format]])
        ) {
            return $this->encoders[$this->encoderByFormat[$format]];
        }

        foreach ($this->encoders as $i => $encoder) {
            if ($encoder->supportsEncoding($format, $context)) {
                $this->encoderByFormat[$format] = $i;

                return $encoder;
            }
        }

        throw new RuntimeException(sprintf('No encoder found for format "%s".', $format));
    }
}
