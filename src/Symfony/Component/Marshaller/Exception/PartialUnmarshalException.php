<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Exception;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.4
 */
final class PartialUnmarshalException extends UnexpectedValueException
{
    /**
     * @param resource                 $resource
     * @param list<ExceptionInterface> $errors
     */
    public function __construct(
        mixed $resource,
        public readonly mixed $unmarshalled,
        public readonly array $errors,
    ) {
        parent::__construct(sprintf('The "%s" resource has been partially unmarshalled.', stream_get_meta_data($resource)['uri']));
    }
}
