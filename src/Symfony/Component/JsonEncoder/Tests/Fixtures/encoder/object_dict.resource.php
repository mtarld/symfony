<?php

return static function (mixed $data, mixed $stream, \Psr\Container\ContainerInterface $normalizers, array $config): void {
    \fwrite($stream, '{');
    $prefix_0 = '';
    foreach ($data as $key_0 => $value_0) {
        $key_0 = \substr(\json_encode($key_0), 1, -1);
        \fwrite($stream, "{$prefix_0}\"{$key_0}\":");
        \fwrite($stream, '{"@id":');
        \fwrite($stream, \json_encode($value_0->id));
        \fwrite($stream, ',"name":');
        \fwrite($stream, \json_encode($value_0->name));
        \fwrite($stream, '}');
        $prefix_0 = ',';
    }
    \fwrite($stream, '}');
};
