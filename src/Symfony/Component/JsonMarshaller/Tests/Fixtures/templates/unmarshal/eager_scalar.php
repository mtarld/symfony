<?php

/**
 * @param resource $resource
 * @return int
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["int"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        try {
            return (int) ($data);
        } catch (\Throwable $e) {
            throw new \Symfony\Component\JsonMarshaller\Exception\UnexpectedValueException(sprintf("Cannot cast \"%s\" to \"int\"", get_debug_type($data)));
        }
    };
    return ($providers["int"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $config));
};
