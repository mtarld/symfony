<?php

/**
 * @param ?array<int,Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy> $data
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
            \fwrite($resource, "{\"id\":");
            \fwrite($resource, \json_encode($value_0->id, $jsonEncodeFlags));
            \fwrite($resource, ",\"name\":");
            \fwrite($resource, \json_encode($value_0->name, $jsonEncodeFlags));
            \fwrite($resource, "}");
            $prefix_0 = ",";
        }
        \fwrite($resource, "]");
    }
};
