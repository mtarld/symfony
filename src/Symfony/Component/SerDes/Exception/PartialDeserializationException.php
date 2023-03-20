<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Exception;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.3
 */
final class PartialDeserializationException extends UnexpectedValueException
{
    /**
     * @param resource                 $resource
     * @param list<ExceptionInterface> $errors
     */
    public function __construct(
        mixed $resource,
        public readonly mixed $deserialized,
        public readonly array $errors,
    ) {
        parent::__construct(sprintf('The "%s" resource has been partially deserialized.', stream_get_meta_data($resource)['uri']));
    }
}
