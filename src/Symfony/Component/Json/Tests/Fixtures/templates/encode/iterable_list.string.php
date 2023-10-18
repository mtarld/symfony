<?php

/**
 * @param iterable<int,mixed> $data
 */
return static function (mixed $data, array $config, ?\Psr\Container\ContainerInterface $services): \Traversable {
    $flags = $config["json_encode_flags"] ?? 0;
    yield \json_encode($data, $flags);
};
