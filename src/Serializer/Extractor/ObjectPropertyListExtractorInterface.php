<?php

namespace App\Serializer\Extractor;

interface ObjectPropertyListExtractorInterface
{
    public function getProperties(object $object): array;
}

