<?php

declare(strict_types=1);

namespace Symfony\Component\NewSerializer\Extractor;

interface ObjectPropertyListExtractorInterface
{
    public function getProperties(object $object): array;
}
