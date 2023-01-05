<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser;

use Symfony\Component\Marshaller\Internal\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
interface ScalarParserInterface
{
    /**
     * @param \Iterator<string>    $tokens
     * @param array<string, mixed> $context
     */
    public function parse(\Iterator $tokens, Type $type, array $context): int|float|string|bool|null;
}
