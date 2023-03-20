<?php

return static function (mixed $data, array $config, ?\Psr\Container\ContainerInterface $services) : \Traversable {
    $flags = $config['json_encode_flags'] ?? 0;
    (yield '{"name":');
    (yield \json_encode($data->name, $flags));
    (yield ',"otherDummyOne":{"@id":');
    (yield \json_encode($data->otherDummyOne->id, $flags));
    (yield ',"name":');
    (yield \json_encode($data->otherDummyOne->name, $flags));
    (yield '},"otherDummyTwo":');
    (yield \json_encode($data->otherDummyTwo, $flags));
    (yield '}');
};
