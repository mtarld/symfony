<?php

/**
 * @param Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes $data
 */
return static function (mixed $data, \Symfony\Component\Encoder\Stream\StreamWriterInterface $stream, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $flags = $config["json_encode_flags"] ?? 0;
    $stream->write("{\"@id\":");
    $stream->write(\json_encode($data->id, $flags));
    $stream->write(",\"name\":");
    $stream->write(\json_encode($data->name, $flags));
    $stream->write("}");
};
