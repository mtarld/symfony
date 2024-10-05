<?php

return static function (mixed $data, mixed $stream, \Psr\Container\ContainerInterface $normalizers, array $config): void {
    \fwrite($stream, $data ? 'true' : 'false');
};
