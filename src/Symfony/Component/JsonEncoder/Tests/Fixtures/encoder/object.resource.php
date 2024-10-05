<?php

return static function (mixed $data, mixed $stream, \Psr\Container\ContainerInterface $normalizers, array $config): void {
    \fwrite($stream, '{"@id":');
    \fwrite($stream, \json_encode($data->id));
    \fwrite($stream, ',"name":');
    \fwrite($stream, \json_encode($data->name));
    \fwrite($stream, '}');
};
