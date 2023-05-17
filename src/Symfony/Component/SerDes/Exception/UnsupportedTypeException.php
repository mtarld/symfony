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
final class UnsupportedTypeException extends InvalidArgumentException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('"%s" type is not supported.', $type));
    }
}
