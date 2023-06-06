<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
interface TypeExtractorInterface
{
    public function extractFromProperty(\ReflectionProperty $property): Type|UnionType;

    public function extractFromFunctionReturn(\ReflectionFunctionAbstract $function): Type|UnionType;

    public function extractFromFunctionParameter(\ReflectionParameter $parameter): Type|UnionType;

    /**
     * @param \ReflectionClass<object> $class
     *
     * @return list<string>
     */
    public function extractTemplateFromClass(\ReflectionClass $class): array;
}
