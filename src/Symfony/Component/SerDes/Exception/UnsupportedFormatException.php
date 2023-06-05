<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Exception;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class UnsupportedFormatException extends InvalidArgumentException
{
    public function __construct(string $format)
    {
        parent::__construct(sprintf('"%s" format is not supported.', $format));
    }
}
