<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Template;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class Template
{
    /**
     * @param callable(): string $content
     */
    public function __construct(
        public string $path,
        private mixed $contentGenerator,
    ) {
    }

    public function content(): string
    {
        return ($this->contentGenerator)();
    }
}

