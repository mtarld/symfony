<?php

/**
 * @param resource $resource
 * @return mixed
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["mixed"] = static function (mixed $resource, int $offset, int $length) use ($config, $instantiator, &$providers): mixed {
        $data = "\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, $offset, $length, $config);
        return $data;
    };
    return ($providers["mixed"])($resource, 0, -1);
};
