<?php

namespace Symfony\Component\JsonEncoder\Tests\Fixtures\Denormalizer;

use Symfony\Component\JsonEncoder\Decode\Denormalizer\DenormalizerInterface;
use Symfony\Component\TypeInfo\Type;

final class DivideStringAndCastToIntDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, array $config): mixed
    {
        return (int) (((int) $data) / (2 * $config['scale']));
    }

    public static function getNormalizedType(): Type
    {
        return Type::string();
    }
}
