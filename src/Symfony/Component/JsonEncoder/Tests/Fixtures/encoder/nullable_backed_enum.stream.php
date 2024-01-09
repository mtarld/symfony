<?php

return static function (mixed $data, \Symfony\Component\JsonEncoder\Stream\StreamWriterInterface $stream, array $config, ?\Psr\Container\ContainerInterface $services) : void {
    $flags = $config['json_encode_flags'] ?? 0;
    if ($data instanceof \Symfony\Component\JsonEncoder\Tests\Fixtures\Enum\DummyBackedEnum) {
        $stream->write(\json_encode($data->value, $flags));
    } elseif (null === $data) {
        $stream->write('null');
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data)));
    }
};
