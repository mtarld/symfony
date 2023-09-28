<?php

/**
 * @param resource $resource
 * @return Symfony\Component\JsonMarshaller\Tests\Fixtures\Enum\DummyBackedEnum
 */
return static function (mixed $resource, array $config, \Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface $instantiator, \Psr\Container\ContainerInterface $services): mixed {
    $providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Enum\\DummyBackedEnum"] = static function (mixed $data) use ($config, $instantiator, &$providers): mixed {
        try {
            return "Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Enum\\DummyBackedEnum"::from($data);
        } catch (\ValueError $e) {
            throw new \Symfony\Component\JsonMarshaller\Exception\UnexpectedValueException(sprintf("Unexpected \"%s\" value for \"Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Enum\\DummyBackedEnum\" backed enumeration.", $data));
        }
    };
    return ($providers["Symfony\\Component\\JsonMarshaller\\Tests\\Fixtures\\Enum\\DummyBackedEnum"])("\\Symfony\\Component\\JsonMarshaller\\Unmarshal\\Template\\Decoder"::decode($resource, 0, -1, $config));
};
