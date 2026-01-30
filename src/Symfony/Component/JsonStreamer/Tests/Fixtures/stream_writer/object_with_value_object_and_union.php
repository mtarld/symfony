<?php

/**
 * @param Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithValueObjectAndUnion $data
 */
return static function (mixed $data, \Psr\Container\ContainerInterface $transformers, array $options): \Traversable {
    try {
        $prefix1 = '';
        yield "{{$prefix1}\"dateTimeOrInt\":";
        if ($data->dateTimeOrInt instanceof \DateTimeInterface) {
            yield \json_encode($transformers->get('DateTimeInterface')->transform($data->dateTimeOrInt, $options), \JSON_THROW_ON_ERROR, 511);
        } elseif (\is_int($data->dateTimeOrInt)) {
            yield \json_encode($data->dateTimeOrInt, \JSON_THROW_ON_ERROR, 511);
        } else {
            throw new \Symfony\Component\JsonStreamer\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data->dateTimeOrInt)));
        }
        yield "}";
    } catch (\JsonException $e) {
        throw new \Symfony\Component\JsonStreamer\Exception\NotEncodableValueException($e->getMessage(), 0, $e);
    }
};
