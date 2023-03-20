<?php

return static function (mixed $data, array $config, ?\Psr\Container\ContainerInterface $services) : \Traversable {
    $flags = $config['json_encode_flags'] ?? 0;
    if (null === $data) {
        (yield 'null');
    } else {
        (yield '[');
        $prefix_0 = '';
        foreach ($data as $value_0) {
            (yield $prefix_0);
            (yield '{"@id":');
            (yield \json_encode($value_0->id, $flags));
            (yield ',"name":');
            (yield \json_encode($value_0->name, $flags));
            (yield '}');
            $prefix_0 = ',';
        }
        (yield ']');
    }
};
