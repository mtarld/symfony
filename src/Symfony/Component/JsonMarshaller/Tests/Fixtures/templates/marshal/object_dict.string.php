<?php

/**
 * @param array<string, Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy> $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $jsonEncodeFlags = $config["json_encode_flags"] ?? 0;
    \fwrite($resource, \json_encode($data, $jsonEncodeFlags));
};
