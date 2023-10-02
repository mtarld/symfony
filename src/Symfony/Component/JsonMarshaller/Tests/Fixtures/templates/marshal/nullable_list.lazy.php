<?php

/**
 * @param ?array<int, mixed> $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $jsonEncodeFlags = $config["json_encode_flags"] ?? 0;
    if (null === $data) {
        \fwrite($resource, "null");
    } else {
        \fwrite($resource, "[");
        $prefix_0 = "";
        foreach ($data as $value_0) {
            \fwrite($resource, $prefix_0);
            \fwrite($resource, \json_encode($value_0, $jsonEncodeFlags));
            $prefix_0 = ",";
        }
        \fwrite($resource, "]");
    }
};
