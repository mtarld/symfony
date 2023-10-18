<?php

/**
 * @param Symfony\Component\Json\Tests\Fixtures\Enum\DummyBackedEnum $data
 */
return static function (mixed $data, \Symfony\Component\Encoder\Stream\StreamWriterInterface $stream, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $flags = $config["json_encode_flags"] ?? 0;
    $stream->write(\json_encode($data->value, $flags));
};
