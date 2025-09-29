<?php

/**
 * @param Symfony\Component\JsonStreamer\Tests\Fixtures\Model\DummyWithNullableProperties $data
 */
return static function (mixed $data, \Psr\Container\ContainerInterface $valueTransformers, array $options): \Traversable {
    try {
        $prefix1 = '';
        yield "{";
        if (null === $data->name && ($options['include_null_properties'] ?? false)) {
            yield "{$prefix1}\"name\":null";
            $prefix1 = ',';
        } elseif (null !== $data->name) {
            yield "{$prefix1}\"name\":";
            yield \json_encode($data->name, \JSON_THROW_ON_ERROR, 511);
            $prefix1 = ',';
        }
        if (null === $data->enum && ($options['include_null_properties'] ?? false)) {
            yield "{$prefix1}\"enum\":null";
        } elseif (null !== $data->enum) {
            yield "{$prefix1}\"enum\":";
            yield \json_encode($data->enum->value, \JSON_THROW_ON_ERROR, 511);
        }
        yield "}";
    } catch (\JsonException $e) {
        throw new \Symfony\Component\JsonStreamer\Exception\NotEncodableValueException($e->getMessage(), 0, $e);
    }
};
