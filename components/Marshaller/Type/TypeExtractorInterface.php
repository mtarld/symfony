<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Type;

interface TypeExtractorInterface
{
    public function extractFromProperty(\ReflectionProperty $property): string;

    public function extractFromReturnType(\ReflectionFunctionAbstract $function): string;
}
