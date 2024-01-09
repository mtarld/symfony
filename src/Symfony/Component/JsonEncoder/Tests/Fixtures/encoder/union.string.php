<?php

return static function (mixed $data, array $config, ?\Psr\Container\ContainerInterface $services) : \Traversable {
    $flags = $config['json_encode_flags'] ?? 0;
    if ($data instanceof \Symfony\Component\JsonEncoder\Tests\Fixtures\Model\DummyWithNameAttributes) {
        (yield '{"@id":');
        (yield \json_encode($data->id, $flags));
        (yield ',"name":');
        (yield \json_encode($data->name, $flags));
        (yield '}');
    } elseif (\is_array($data)) {
        (yield '[');
        $prefix_0 = '';
        foreach ($data as $value_0) {
            (yield $prefix_0);
            (yield \json_encode($value_0->value, $flags));
            $prefix_0 = ',';
        }
        (yield ']');
    } elseif (\is_int($data)) {
        (yield \json_encode($data, $flags));
    } else {
        throw new \Symfony\Component\JsonEncoder\Exception\UnexpectedValueException(\sprintf('Unexpected "%s" value.', \get_debug_type($data)));
    }
};
