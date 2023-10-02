<?php

/**
 * @param ?Symfony\Component\JsonMarshaller\Tests\Fixtures\Enum\DummyBackedEnum $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $jsonEncodeFlags = $config["json_encode_flags"] ?? 0;
    if (null === $data) {
        \fwrite($resource, "null");
    } else {
        \fwrite($resource, \json_encode($data->value, $jsonEncodeFlags));
    }
};
