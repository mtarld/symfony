<?php

/**
 * @param array<int,Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes> $data
 * @param resource $stream
 */
return static function (mixed $data, mixed $stream, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $flags = $config["json_encode_flags"] ?? 0;
    \fwrite($stream, "[");
    $prefix_0 = "";
    foreach ($data as $value_0) {
        \fwrite($stream, $prefix_0);
        \fwrite($stream, "{\"@id\":");
        \fwrite($stream, \json_encode($value_0->id, $flags));
        \fwrite($stream, ",\"name\":");
        \fwrite($stream, \json_encode($value_0->name, $flags));
        \fwrite($stream, "}");
        $prefix_0 = ",";
    }
    \fwrite($stream, "]");
};
