<?php

return static function (mixed $data, \Symfony\Component\JsonEncoder\Stream\StreamWriterInterface $stream, array $config): void {
    $stream->write('{"@id":');
    $stream->write(\json_encode($data->id));
    $stream->write(',"name":');
    $stream->write(\json_encode($data->name));
    $stream->write('}');
};
