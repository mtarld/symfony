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

use PhpParser\Node\Expr;
use Symfony\Component\JsonEncoder\DataModel\DataAccessorInterface;

/**
 * Defines the way to access data using PHP AST.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final readonly class PhpExprDataAccessor implements DataAccessorInterface
{
    public function __construct(
        public Expr $php,
    ) {
    }
}
