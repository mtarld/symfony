<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Exception;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class InvalidTypeException extends InvalidArgumentException
{
    public function __construct(string $type, string $cause = null)
    {
        $message = sprintf('Invalid "%s" type', $type);

        if (null !== $cause) {
            $message .= ': '.$cause;
        }

        parent::__construct($message.'.');
    }
}
