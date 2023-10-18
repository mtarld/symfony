<?php

/**
 * @param ?array<int,Symfony\Component\Json\Tests\Fixtures\Model\DummyWithNameAttributes> $data
 */
return static function (mixed $data, \Symfony\Component\Encoder\Stream\StreamWriterInterface $stream, array $config, ?\Psr\Container\ContainerInterface $services): void {
    $flags = $config["json_encode_flags"] ?? 0;
    if (null === $data) {
        $stream->write("null");
    } else {
        $stream->write("[");
        $prefix_0 = "";
        foreach ($data as $value_0) {
            $stream->write($prefix_0);
            $stream->write("{\"@id\":");
            $stream->write(\json_encode($value_0->id, $flags));
            $stream->write(",\"name\":");
            $stream->write(\json_encode($value_0->name, $flags));
            $stream->write("}");
            $prefix_0 = ",";
        }
        $stream->write("]");
    }
};
