<?php

/**
 * @param resource $resource
 * @return ?string
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["?string"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): mixed {
        $data = "\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, $offset, $length, $config);
        if (null === $data) {
            return null;
        }
        try {
            return (string) ($data);
        } catch (\Throwable $e) {
            throw new \Symfony\Component\JsonMarshaller\Exception\UnexpectedValueException(sprintf("Cannot cast \"%s\" to \"string\"", get_debug_type($data)));
        }
    };
    return ($providers["?string"])($resource, 0, -1);
};
