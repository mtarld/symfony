<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\DataModel;

/**
 * Defines the way to access data using a function (or a method).
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class FunctionDataAccessor implements DataAccessorInterface
{
    /**
     * @param list<DataAccessorInterface> $arguments
     */
    public function __construct(
        private string $functionName,
        private array $arguments,
        private ?DataAccessorInterface $objectAccessor = null,
    ) {
    }

    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * @return list<DataAccessorInterface>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getObjectAccessor(): ?DataAccessorInterface
    {
        return $this->objectAccessor;
    }
}
