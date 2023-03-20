<?php

namespace Symfony\Component\SerDes\Tests\Fixtures\Dto;

use Symfony\Component\SerDes\Attribute\Serializable;

/**
 * @template Tk of \Stringable
 * @template Tk of object
 */
#[Serializable]
class NonUniqueTemplatePhpstanExtractableDummy
{
}
