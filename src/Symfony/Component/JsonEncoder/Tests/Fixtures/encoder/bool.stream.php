<?php

return static function (mixed $data, \Symfony\Component\JsonEncoder\Stream\StreamWriterInterface $stream, \Psr\Container\ContainerInterface $normalizers, array $config): void {
    $stream->write($data ? 'true' : 'false');
};
