<?php

return static function (mixed $data, \Symfony\Component\Encoder\Stream\StreamWriterInterface $stream, array $config, ?\Psr\Container\ContainerInterface $services) : void {
    $flags = $config['json_encode_flags'] ?? 0;
    $stream->write('{"name":');
    $stream->write(\json_encode($data->name, $flags));
    $stream->write(',"otherDummyOne":{"@id":');
    $stream->write(\json_encode($data->otherDummyOne->id, $flags));
    $stream->write(',"name":');
    $stream->write(\json_encode($data->otherDummyOne->name, $flags));
    $stream->write('},"otherDummyTwo":');
    $stream->write(\json_encode($data->otherDummyTwo, $flags));
    $stream->write('}');
};
