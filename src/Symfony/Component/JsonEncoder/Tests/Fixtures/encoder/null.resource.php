<?php

return static function (mixed $data, mixed $stream, array $config, ?\Psr\Container\ContainerInterface $services): void {
    \fwrite($stream, 'null');
};
