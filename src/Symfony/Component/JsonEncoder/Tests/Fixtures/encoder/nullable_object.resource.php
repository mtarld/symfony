<?php

return static function (mixed $data, mixed $stream, array $config, ?\Psr\Container\ContainerInterface $services) : void {
    $flags = $config['json_encode_flags'] ?? 0;
    if ($data instanceof \Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes) {
        \fwrite($stream, '{"@id":');
        \fwrite($stream, \json_encode($data->id, $flags));
        \fwrite($stream, ',"name":');
        \fwrite($stream, \json_encode($data->name, $flags));
        \fwrite($stream, '}');
    } elseif (null === $data) {
        \fwrite($stream, 'null');
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data)));
    }
};
