<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\PropertyConfigurator;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface DeserializePropertyConfiguratorInterface
{
    /**
     * @param class-string              $className
     * @param array<string, callable(): mixed> $properties
     * @param array<string, mixed> $context
     *
     * @return array<string, callable(): mixed>
     */
    public function configure(string $className, array $properties, array $context): array;
}
