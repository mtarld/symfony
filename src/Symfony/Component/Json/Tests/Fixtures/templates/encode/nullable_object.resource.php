<?php

/**
 * @param ?Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes $data
 * @param resource $stream
 */
return static function (mixed $data, mixed $stream, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $flags = $config["json_encode_flags"] ?? 0;
    if (null === $data) {
        \fwrite($stream, "null");
    } else {
        \fwrite($stream, "{\"@id\":");
        \fwrite($stream, \json_encode($data->id, $flags));
        \fwrite($stream, ",\"name\":");
        \fwrite($stream, \json_encode($data->name, $flags));
        \fwrite($stream, "}");
    }
};
