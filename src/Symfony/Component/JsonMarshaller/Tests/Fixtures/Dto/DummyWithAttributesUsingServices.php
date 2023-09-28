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
    #[MaxDepth(1, [self::class, 'invalidNullableService'])]
    public string $two = 'two';

    #[MarshalFormatter([self::class, 'invalidOptionalService'])]
    #[MaxDepth(1, [self::class, 'skippedUnknownService'])]
    public string $three = 'three';

    public static function serviceAndConfig(string $value, TypeExtractorInterface $service, array $config): string
    {
        return 'useless';
    }

    public static function autowireAttribute(string $value, #[Autowire(service: 'marshaller.type_extractor')] $service): string
    {
        return 'useless';
    }

    public static function invalidNullableService(string $value, ?\InvalidInterface $invalid): string
    {
        return 'useless';
    }

    public static function invalidOptionalService(string $value, \InvalidInterface $invalid = null): string
    {
        return 'useless';
    }

    public static function skippedUnknownService(string $value, $skipped): string
    {
        return 'useless';
    }
}
