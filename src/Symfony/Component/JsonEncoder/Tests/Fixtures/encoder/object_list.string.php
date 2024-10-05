<?php

return static function (mixed $data, \Psr\Container\ContainerInterface $normalizers, array $config): \Traversable {
    yield '[';
    $prefix_0 = '';
    foreach ($data as $value_0) {
        yield $prefix_0;
        yield '{"@id":';
        yield \json_encode($value_0->id);
        yield ',"name":';
        yield \json_encode($value_0->name);
        yield '}';
        $prefix_0 = ',';
    }
    yield ']';
};
