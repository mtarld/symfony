<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Parser\Json;

use Symfony\Component\Marshaller\Internal\Parser\NullableParserInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class JsonNullableParser implements NullableParserInterface
{
    public function parse(\Iterator $tokens, callable $handle, array $context): mixed
    {
        $token = $tokens->current();

        if ('null' === $token) {
            $tokens->next();

            return null;
        }

        return $handle($tokens);
    }
}
