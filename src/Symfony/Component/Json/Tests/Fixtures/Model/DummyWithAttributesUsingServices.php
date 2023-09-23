<?php

namespace Symfony\Component\Json\Tests\Fixtures\Model;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Encoder\Attribute\DecodeFormatter;
use Symfony\Component\Encoder\Attribute\MaxDepth;
use Symfony\Component\Encoder\Attribute\EncodeFormatter;
use Symfony\Component\Encoder\DecoderInterface;
use Symfony\Component\TypeInfo\Type;

class DummyWithAttributesUsingServices
{
    #[DecodeFormatter([self::class, 'serviceAndConfig'])]
    public string $one = 'one';

    #[EncodeFormatter([self::class, 'autowireAttribute'])]
    public string $two = 'two';

    #[MaxDepth(1, [self::class, 'skippedUnknownService'])]
    public string $three = 'three';

    public static function serviceAndConfig(string $value, DecoderInterface $service, array $config): string
    {
        return $service->decode($value, Type::string());
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
