<?php

/**
 * @param ?Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $jsonEncodeFlags = $config["json_encode_flags"] ?? 0;
    if (null === $data) {
        \fwrite($resource, "null");
    } else {
        \fwrite($resource, "{\"id\":");
        \fwrite($resource, \json_encode($data->id, $jsonEncodeFlags));
        \fwrite($resource, ",\"name\":");
        \fwrite($resource, \json_encode($data->name, $jsonEncodeFlags));
        \fwrite($resource, "}");
    }
};
