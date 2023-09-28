<?php

/**
 * @param mixed $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, array $config, \Psr\Container\ContainerInterface $services): void {
    \fwrite($resource, \json_encode($data, $config["json_encode_flags"]));
};
