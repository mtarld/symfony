<?php

return static function (mixed $data, \Symfony\Component\JsonEncoder\Stream\StreamWriterInterface $stream, array $config, ?\Psr\Container\ContainerInterface $services) : void {
    $flags = $config['json_encode_flags'] ?? 0;
    if ($data instanceof \Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes) {
        $stream->write('{"@id":');
        $stream->write(\json_encode($data->id, $flags));
        $stream->write(',"name":');
        $stream->write(\json_encode($data->name, $flags));
        $stream->write('}');
    } elseif (\is_array($data)) {
        $stream->write('[');
        $prefix_0 = '';
        foreach ($data as $value_0) {
            $stream->write($prefix_0);
            $stream->write(\json_encode($value_0->value, $flags));
            $prefix_0 = ',';
        }
        $stream->write(']');
    } elseif (\is_int($data)) {
        $stream->write(\json_encode($data, $flags));
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data)));
    }
};
