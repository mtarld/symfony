<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface TypeExtractorInterface
{
    public function extractFromProperty(\ReflectionProperty $property): Type;

    public function extractFromFunctionReturn(\ReflectionFunctionAbstract $function): Type;

    public function extractFromFunctionParameter(\ReflectionParameter $parameter): Type;

    /**
     * @param \ReflectionClass<object> $class
     *
     * @return list<string>
     */
    public function extractTemplateFromClass(\ReflectionClass $class): array;
}
