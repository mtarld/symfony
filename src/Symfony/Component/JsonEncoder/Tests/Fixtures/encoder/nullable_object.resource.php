<?php

return static function (mixed $data, mixed $stream, array $config): void {
    if ($data instanceof \Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes) {
        \fwrite($stream, '{"@id":');
        \fwrite($stream, \json_encode($data->id));
        \fwrite($stream, ',"name":');
        \fwrite($stream, \json_encode($data->name));
        \fwrite($stream, '}');
    } elseif (null === $data) {
        \fwrite($stream, 'null');
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data)));
    }
};
