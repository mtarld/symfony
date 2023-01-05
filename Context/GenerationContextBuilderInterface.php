<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
interface GenerationContextBuilderInterface
{
    /**
     * @param array<string, mixed> $rawContext
     *
     * @return array<string, mixed>
     */
    public function build(string $type, Context $context, array $rawContext): array;
}
