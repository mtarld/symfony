<?php

return static function (mixed $data, mixed $stream, \Psr\Container\ContainerInterface $normalizers, array $config): void {
    \fwrite($stream, '{"id":');
    \fwrite($stream, \json_encode($normalizers->get('Symfony\Component\JsonEncoder\Tests\Fixtures\Normalizer\DoubleIntAndCastToStringNormalizer')->normalize($data->id, $config)));
    \fwrite($stream, ',"active":');
    \fwrite($stream, \json_encode($normalizers->get('Symfony\Component\JsonEncoder\Tests\Fixtures\Normalizer\BooleanStringNormalizer')->normalize($data->active, $config)));
    \fwrite($stream, '}');
};
