<?php

namespace Symfony\Component\JsonEncoder\Tests\Fixtures\Denormalizer;

use Symfony\Component\JsonEncoder\Denormalizer\DenormalizerInterface;
use Symfony\Component\TypeInfo\Type;

final class BooleanStringDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, array $config): mixed
    {
        return 'true' === $data;
    }

    public static function getNormalizedType(): Type
    {
        return Type::string();
    }
}
