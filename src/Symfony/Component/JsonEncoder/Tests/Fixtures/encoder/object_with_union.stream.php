<?php

return static function (mixed $data, \Symfony\Component\JsonEncoder\Stream\StreamWriterInterface $stream, array $config, ?\Psr\Container\ContainerInterface $services) : void {
    $flags = $config['json_encode_flags'] ?? 0;
    $stream->write('{"value":');
    if ($data->value instanceof \Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum) {
        $stream->write(\json_encode($data->value->value, $flags));
    } elseif (null === $data->value) {
        $stream->write('null');
    } elseif (\is_string($data->value)) {
        $stream->write(\json_encode($data->value, $flags));
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data->value)));
    }
    $stream->write('}');
};
