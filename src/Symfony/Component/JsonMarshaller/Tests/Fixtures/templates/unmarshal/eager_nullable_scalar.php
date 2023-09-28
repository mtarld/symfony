<?php

/**
 * @param resource $resource
 * @return ?string
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["?string"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        if (null === $data) {
            return null;
        }
        try {
            return (string) ($data);
        } catch (\Throwable $e) {
            throw new \Symfony\Component\JsonMarshaller\Exception\UnexpectedValueException(sprintf("Cannot cast \"%s\" to \"string\"", get_debug_type($data)));
        }
    };
    return ($providers["?string"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $config));
};
