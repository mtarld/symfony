<?php

/**
 * @param iterable<string, mixed> $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, array $config, \Psr\Container\ContainerInterface $services): void {
    \fwrite($resource, "{");
    $prefix_0 = "";
    foreach ($data as $key_0 => $value_0) {
        $key_0 = \substr(\json_encode($key_0, $config["json_encode_flags"]), 1, -1);
        \fwrite($resource, "{$prefix_0}\"{$key_0}\":");
        \fwrite($resource, \json_encode($value_0, $config["json_encode_flags"]));
        $prefix_0 = ",";
    }
    \fwrite($resource, "}");
};
