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
use Symfony\Component\Serializer\Context\Encoder\JsonEncoderOptions;

/**
 * Encodes JSON data.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class JsonEncoder implements EncoderInterface, DecoderInterface
{
    public const FORMAT = 'json';

    protected $encodingImpl;
    protected $decodingImpl;

    public function __construct(JsonEncode $encodingImpl = null, JsonDecode $decodingImpl = null)
    {
        $decodingContext = new Context((new JsonEncoderOptions())->setAssociative(true));

        $this->encodingImpl = $encodingImpl ?? new JsonEncode();
        $this->decodingImpl = $decodingImpl ?? new JsonDecode($decodingContext);
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

        return $this->encodingImpl->encode($data, self::FORMAT, $context);
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

        return $this->decodingImpl->decode($data, self::FORMAT, $context);
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
}
