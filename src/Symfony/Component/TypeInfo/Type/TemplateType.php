<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\TypeInfo\Type;

use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 * @author Baptiste Leduc <baptiste.leduc@gmail.com>
 */
final class TemplateType extends Type
{
    public function __construct(
        private readonly string $template,
    ) {
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function __toString(): string
    {
        return $this->template;
    }
}
