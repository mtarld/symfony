<?php

return static function (mixed $data, \Symfony\Component\JsonEncoder\Stream\StreamWriterInterface $stream, array $config, ?\Psr\Container\ContainerInterface $services) : void {
    $flags = $config['json_encode_flags'] ?? 0;
    if (\is_array($data)) {
        $stream->write('[');
        $prefix_0 = '';
        foreach ($data as $value_0) {
            $stream->write($prefix_0);
            $stream->write('{"@id":');
            $stream->write(\json_encode($value_0->id, $flags));
            $stream->write(',"name":');
            $stream->write(\json_encode($value_0->name, $flags));
            $stream->write('}');
            $prefix_0 = ',';
        }
        $stream->write(']');
    } elseif (null === $data) {
        $stream->write('null');
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data)));
    }
};
