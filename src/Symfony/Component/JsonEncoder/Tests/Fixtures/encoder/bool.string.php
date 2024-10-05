<?php

return static function (mixed $data, \Psr\Container\ContainerInterface $normalizers, array $config): \Traversable {
    yield $data ? 'true' : 'false';
};
