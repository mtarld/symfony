<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
interface TypeExtractorInterface
{
    public function extractTypeFromProperty(\ReflectionProperty $property): Type;

    public function extractTypeFromFunctionReturn(\ReflectionFunctionAbstract $function): Type;

    public function extractTypeFromParameter(\ReflectionParameter $parameter): Type;
}
