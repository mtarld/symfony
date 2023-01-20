<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Stream;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
interface StreamInterface extends \Stringable
{
    /**
     * @return resource
     */
    public function resource();
}
