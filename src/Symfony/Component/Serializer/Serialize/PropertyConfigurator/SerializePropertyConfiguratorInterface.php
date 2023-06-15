<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\PropertyConfigurator;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface SerializePropertyConfiguratorInterface
{
    /**
     * @param class-string              $className
     * @param array<string, array{type: Type, accessor: string}> $properties
     * @param array<string, mixed> $context
     *
     * @return array<string, array{type: Type, accessor: string}>
     */
    public function configure(string $className, array $properties, array $context): array;
}
