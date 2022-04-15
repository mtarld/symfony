<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Extractor;

interface ObjectPropertyListExtractorInterface
{
    public function getProperties(object $object): array;
}
