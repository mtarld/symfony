<?php

/**
 * @param ?Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, array $config, \Psr\Container\ContainerInterface $services): void {
    if (null === $data) {
        \fwrite($resource, "null");
    } else {
        \fwrite($resource, "{\"id\":");
        \fwrite($resource, \json_encode($data->id, $config["json_encode_flags"]));
        \fwrite($resource, ",\"name\":");
        \fwrite($resource, \json_encode($data->name, $config["json_encode_flags"]));
        \fwrite($resource, "}");
    }
};
