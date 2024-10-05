<?php

return static function (mixed $data, \Symfony\Component\JsonEncoder\Stream\StreamWriterInterface $stream, \Psr\Container\ContainerInterface $normalizers, array $config): void {
    $stream->write('{"id":');
    $stream->write(\json_encode($normalizers->get('Symfony\Component\JsonEncoder\Tests\Fixtures\Normalizer\DoubleIntAndCastToStringNormalizer')->normalize($data->id, $config)));
    $stream->write(',"active":');
    $stream->write(\json_encode($normalizers->get('Symfony\Component\JsonEncoder\Tests\Fixtures\Normalizer\BooleanStringNormalizer')->normalize($data->active, $config)));
    $stream->write('}');
};
