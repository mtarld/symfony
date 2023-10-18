<?php

/**
 * @param Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes $data
 */
return static function (mixed $data, array $config, ?\Psr\Container\ContainerInterface $services): \Traversable {
    $flags = $config["json_encode_flags"] ?? 0;
    yield "{\"@id\":";
    yield \json_encode($data->id, $flags);
    yield ",\"name\":";
    yield \json_encode($data->name, $flags);
    yield "}";
};
