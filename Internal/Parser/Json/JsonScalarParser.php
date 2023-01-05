<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Parser\ScalarParserInterface;
use Symfony\Component\Marshaller\Internal\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonScalarParser implements ScalarParserInterface
{
    public function parse(\Iterator $tokens, Type $type, array $context): int|float|string|bool|null
    {
        $value = json_decode($tokens->current(), flags: $context['json_decode_flags'] ?? 0);
        $tokens->next();

        return $value;
    }
}
