<?php

namespace Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\JsonMarshaller\Attribute\UnmarshalFormatter;
use Symfony\Component\JsonMarshaller\Attribute\MaxDepth;
use Symfony\Component\JsonMarshaller\Attribute\MarshalFormatter;
use Symfony\Component\JsonMarshaller\Type\TypeExtractorInterface;

class DummyWithAttributesUsingServices
{
    #[UnmarshalFormatter([self::class, 'serviceAndConfig'])]
    public string $one = 'one';

    #[MarshalFormatter([self::class, 'autowireAttribute'])]
    public string $two = 'two';

    #[MaxDepth(1, [self::class, 'skippedUnknownService'])]
    public string $three = 'three';

    public static function serviceAndConfig(string $value, TypeExtractorInterface $service, array $config): string
    {
        return $service->extractTypeFromProperty(new \ReflectionProperty(self::class, 'one'));
    }

    public static function autowireAttribute(string $value, #[Autowire(service: 'custom_service')] $service): string
    {
        return $service('useless');
    }

    public static function skippedUnknownService(string $value, $skipped): string
    {
        return 'useless';
    }
}
