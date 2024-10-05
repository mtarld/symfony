<?php

return static function (mixed $data, \Psr\Container\ContainerInterface $normalizers, array $config): \Traversable {
    yield \json_encode($data);
};
