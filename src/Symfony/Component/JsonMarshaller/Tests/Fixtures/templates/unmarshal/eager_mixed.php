<?php

/**
 * @param resource $resource
 * @return mixed
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["mixed"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        return $data;
    };
    return ($providers["mixed"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $config));
};
