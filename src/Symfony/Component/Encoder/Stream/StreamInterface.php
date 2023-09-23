<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Stream;

/**
 * Holds a PHP resource.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 */
interface StreamInterface extends \Stringable
{
    /**
     * @return resource
     */
    public function getResource(): mixed;
}
