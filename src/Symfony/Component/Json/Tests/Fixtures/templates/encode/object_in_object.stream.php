<?php

/**
 * @param Symfony\Component\Json\Tests\Fixtures\Model\DummyWithOtherDummies $data
 * @param resource $resource
 */
return static function (mixed $data, mixed $resource, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $jsonEncodeFlags = $config["json_encode_flags"] ?? 0;
    \fwrite($resource, "{\"name\":");
    \fwrite($resource, \json_encode($data->name, $jsonEncodeFlags));
    \fwrite($resource, ",\"otherDummyOne\":{\"@id\":");
    \fwrite($resource, \json_encode($data->otherDummyOne->id, $jsonEncodeFlags));
    \fwrite($resource, ",\"name\":");
    \fwrite($resource, \json_encode($data->otherDummyOne->name, $jsonEncodeFlags));
    \fwrite($resource, "},\"otherDummyTwo\":{\"id\":");
    \fwrite($resource, \json_encode($data->otherDummyTwo->id, $jsonEncodeFlags));
    \fwrite($resource, ",\"name\":");
    \fwrite($resource, \json_encode($data->otherDummyTwo->name, $jsonEncodeFlags));
    \fwrite($resource, "}}");
};
