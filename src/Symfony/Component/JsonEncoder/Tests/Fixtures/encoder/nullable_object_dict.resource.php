<?php

return static function (mixed $data, mixed $stream, array $config, ?\Psr\Container\ContainerInterface $services) : void {
    $flags = $config['json_encode_flags'] ?? 0;
    if (null === $data) {
        \fwrite($stream, 'null');
    } else {
        \fwrite($stream, '{');
        $prefix_0 = '';
        foreach ($data as $key_0 => $value_0) {
            $key_0 = \substr(\json_encode($key_0, $flags), 1, -1);
            \fwrite($stream, "{$prefix_0}\"{$key_0}\":");
            \fwrite($stream, '{"@id":');
            \fwrite($stream, \json_encode($value_0->id, $flags));
            \fwrite($stream, ',"name":');
            \fwrite($stream, \json_encode($value_0->name, $flags));
            \fwrite($stream, '}');
            $prefix_0 = ',';
        }
        \fwrite($stream, '}');
    }
};
