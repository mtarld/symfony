<?php

/**
 * @param array<int,mixed> $data
 * @param resource $stream
 */
return static function (mixed $data, mixed $stream, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $flags = $config["json_encode_flags"] ?? 0;
    \fwrite($stream, \json_encode($data, $flags));
};
