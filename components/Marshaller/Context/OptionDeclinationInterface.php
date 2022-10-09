<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

use Symfony\Component\Marshaller\Metadata\ClassMetadata;

interface OptionDeclinationInterface
{
    /**
     * @return list<string>
     */
    public static function resolve(ClassMetadata $classMetadata): array;

    public static function createOption(array $values): OptionInterface;
}
