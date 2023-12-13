<?php

return static function (mixed $data, mixed $stream, array $config, ?\Psr\Container\ContainerInterface $services) : void {
    $flags = $config['json_encode_flags'] ?? 0;
    \fwrite($stream, '{"name":');
    \fwrite($stream, \json_encode($data->name, $flags));
    \fwrite($stream, ',"otherDummyOne":{"@id":');
    \fwrite($stream, \json_encode($data->otherDummyOne->id, $flags));
    \fwrite($stream, ',"name":');
    \fwrite($stream, \json_encode($data->otherDummyOne->name, $flags));
    \fwrite($stream, '},"otherDummyTwo":');
    \fwrite($stream, \json_encode($data->otherDummyTwo, $flags));
    \fwrite($stream, '}');
};
