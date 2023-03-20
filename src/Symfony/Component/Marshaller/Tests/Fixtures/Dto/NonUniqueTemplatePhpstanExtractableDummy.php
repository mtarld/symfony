<?php

namespace Symfony\Component\Marshaller\Tests\Fixtures\Dto;

use Symfony\Component\Marshaller\Attribute\Marshallable;

/**
 * @template Tk of \Stringable
 * @template Tk of object
 */
#[Marshallable]
class NonUniqueTemplatePhpstanExtractableDummy
{
}
