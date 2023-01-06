<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Exception;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class UnknownFormatException extends \InvalidArgumentException
{
    public function __construct(string $format)
    {
        parent::__construct(sprintf('Unknown "%s" format.', $format));
    }
}
