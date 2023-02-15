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
     * @param resource             $resource
     * @param array<string, mixed> $context
     */
    public function parse(mixed $resource, Type $type, array $context): int|float|string|bool|null;
}
