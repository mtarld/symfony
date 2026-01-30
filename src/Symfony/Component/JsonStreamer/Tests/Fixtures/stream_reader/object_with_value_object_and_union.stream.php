<?php

/**
 * @return Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithValueObjectAndUnion
 */
return static function (mixed $stream, \Psr\Container\ContainerInterface $transformers, \Symfony\Component\JsonStreamer\Read\LazyInstantiator $instantiator, array $options): mixed {
    $providers['Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithValueObjectAndUnion'] = static function ($stream, $offset, $length) use ($options, $transformers, $instantiator, &$providers) {
        $data = \Symfony\Component\JsonStreamer\Read\Splitter::splitDict($stream, $offset, $length);
        return $instantiator->instantiate(\Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithValueObjectAndUnion::class, static function ($object) use ($stream, $data, $options, $transformers, $instantiator, &$providers) {
            foreach ($data as $k => $v) {
                match ($k) {
                    'dateTimeOrInt' => $object->dateTimeOrInt = $providers['DateTimeInterface|int']($stream, $v[0], $v[1]),
                    default => null,
                };
            }
        });
    };
    $providers['DateTimeInterface'] = static function ($stream, $offset, $length) use ($options, $transformers, $instantiator, &$providers) {
        return $transformers->get('DateTimeInterface')->reverseTransform(\Symfony\Component\JsonStreamer\Read\Decoder::decodeStream($stream, $offset, $length), $options);
    };
    $providers['DateTimeInterface|int'] = static function ($stream, $offset, $length) use ($options, $transformers, $instantiator, &$providers) {
        $data = \Symfony\Component\JsonStreamer\Read\Decoder::decodeStream($stream, $offset, $length);
        if (\is_string($data)) {
            return $providers['DateTimeInterface']($stream, $offset, $length);
        }
        if (\is_int($data)) {
            return $data;
        }
        throw new \Symfony\Component\JsonStreamer\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value for "DateTimeInterface|int".', \get_debug_type($data)));
    };
    return $providers['Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithValueObjectAndUnion']($stream, 0, null);
};
