<?php

return static function (mixed $data, \Psr\Container\ContainerInterface $normalizers, array $config): \Traversable {
    yield '{"@id":';
    yield \json_encode($data->id);
    yield ',"name":';
    yield \json_encode($data->name);
    yield '}';
};
