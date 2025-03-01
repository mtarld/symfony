<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonStreamer\Tests\Fixtures\Model;

use Symfony\Component\JsonStreamer\Attribute\ValueTransformer;

class DummyWithUnionAndValueTransformer
{
    public \DateTimeInterface|bool $dateTimeOrBool;

    // #[ValueTransformer(nativeToStream: 'strtoupper', streamToNative: 'strtolower')]
    // public string|bool $bar;
}
