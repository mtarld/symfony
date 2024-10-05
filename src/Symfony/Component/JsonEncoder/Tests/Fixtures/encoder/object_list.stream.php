<?php

return static function (mixed $data, \Symfony\Component\JsonEncoder\Stream\StreamWriterInterface $stream, \Psr\Container\ContainerInterface $normalizers, array $config): void {
    $stream->write('[');
    $prefix_0 = '';
    foreach ($data as $value_0) {
        $stream->write($prefix_0);
        $stream->write('{"@id":');
        $stream->write(\json_encode($value_0->id));
        $stream->write(',"name":');
        $stream->write(\json_encode($value_0->name));
        $stream->write('}');
        $prefix_0 = ',';
    }
    $stream->write(']');
};
