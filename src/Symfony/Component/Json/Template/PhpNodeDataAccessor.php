<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Template;

use Symfony\Component\Encoder\DataModel\DataAccessorInterface;
use Symfony\Component\Json\Php\PhpNodeInterface;

/**
 * Defines the way to access data using PHP AST.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class PhpNodeDataAccessor implements DataAccessorInterface
{
    public function __construct(
        public PhpNodeInterface $php,
    ) {
    }
}
