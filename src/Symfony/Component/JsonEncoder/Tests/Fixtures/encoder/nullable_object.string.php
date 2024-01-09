<?php

return static function (mixed $data, array $config, ?\Psr\Container\ContainerInterface $services) : \Traversable {
    $flags = $config['json_encode_flags'] ?? 0;
    if ($data instanceof \Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes) {
        (yield '{"@id":');
        (yield \json_encode($data->id, $flags));
        (yield ',"name":');
        (yield \json_encode($data->name, $flags));
        (yield '}');
    } elseif (null === $data) {
        (yield 'null');
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data)));
    }
};
