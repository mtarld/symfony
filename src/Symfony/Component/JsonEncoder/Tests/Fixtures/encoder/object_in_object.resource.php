<?php

return static function (mixed $data, mixed $stream, \Psr\Container\ContainerInterface $normalizers, array $config): void {
    \fwrite($stream, '{"name":');
    \fwrite($stream, \json_encode($data->name));
    \fwrite($stream, ',"otherDummyOne":{"@id":');
    \fwrite($stream, \json_encode($data->otherDummyOne->id));
    \fwrite($stream, ',"name":');
    \fwrite($stream, \json_encode($data->otherDummyOne->name));
    \fwrite($stream, '},"otherDummyTwo":');
    \fwrite($stream, \json_encode($data->otherDummyTwo));
    \fwrite($stream, '}');
};
