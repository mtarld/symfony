<?php

return static function (mixed $data, \Symfony\Component\JsonEncoder\Stream\StreamWriterInterface $stream, array $config, ?\Psr\Container\ContainerInterface $services) : void {
    $flags = $config['json_encode_flags'] ?? 0;
    $stream->write('{');
    $prefix_0 = '';
    foreach ($data as $key_0 => $value_0) {
        $key_0 = \substr(\json_encode($key_0, $flags), 1, -1);
        $stream->write("{$prefix_0}\"{$key_0}\":");
        $stream->write('{"@id":');
        $stream->write(\json_encode($value_0->id, $flags));
        $stream->write(',"name":');
        $stream->write(\json_encode($value_0->name, $flags));
        $stream->write('}');
        $prefix_0 = ',';
    }
    $stream->write('}');
};
