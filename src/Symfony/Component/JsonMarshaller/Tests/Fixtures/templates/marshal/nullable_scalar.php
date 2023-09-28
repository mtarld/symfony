<?php

/**
 * @param ?string $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, array $config, \Psr\Container\ContainerInterface $services): void {
    if (null === $data) {
        \fwrite($resource, "null");
    } else {
        \fwrite($resource, \json_encode($data, $config["json_encode_flags"]));
    }
};
