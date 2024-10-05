<?php

return static function (mixed $data, mixed $stream, \Psr\Container\ContainerInterface $normalizers, array $config): void {
    \fwrite($stream, \json_encode($data->value));
};
