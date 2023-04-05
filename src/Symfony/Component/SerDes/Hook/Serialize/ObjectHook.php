<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\SerDes\Hook\Serialize;

use Symfony\Component\SerDes\Exception\InvalidArgumentException;
use Symfony\Component\SerDes\Type\TypeExtractorInterface;
use Symfony\Component\SerDes\Type\TypeGenericsHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class ObjectHook implements ObjectHookInterface
{
    private readonly TypeGenericsHelper $typeGenericsHelper;

    public function __construct(
        private readonly TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeGenericsHelper = new TypeGenericsHelper();
    }

    public function __invoke(string $type, string $accessor, array $context): array
    {
        return [
            'context' => $this->addGenericParameterTypes($type, $context),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function addGenericParameterTypes(string $type, array $context): array
    {
        $generics = $this->typeGenericsHelper->extractGenerics($type);

        $genericType = $generics['genericType'];
        $genericParameters = $generics['genericParameters'];

        if (!class_exists($genericType)) {
            return $context;
        }

        $templates = $this->typeExtractor->extractTemplateFromClass(new \ReflectionClass($genericType));

        if (\count($templates) !== \count($genericParameters)) {
            throw new InvalidArgumentException(sprintf('Given %d generic parameters in "%s", but %d templates are defined in "%s".', \count($genericParameters), $type, \count($templates), $genericType));
        }

        foreach ($genericParameters as $i => $genericParameter) {
            $context['_symfony']['generic_parameter_types'][$genericType][$templates[$i]] = $genericParameter;
        }

        return $context;
    }
}
