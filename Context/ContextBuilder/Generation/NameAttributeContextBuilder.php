<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder\Generation;

use Symfony\Component\Marshaller\Attribute\Name;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\GenerationContextBuilderInterface;
use Symfony\Component\Marshaller\Type\TypeHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class NameAttributeContextBuilder implements GenerationContextBuilderInterface
{
    private readonly TypeHelper $typeHelper;

    public function __construct()
    {
        $this->typeHelper = new TypeHelper();
    }

    public function build(string $type, Context $context, array $rawContext): array
    {
        foreach ($this->typeHelper->extractClassNames($type) as $className) {
            $rawContext = $this->addPropertyNames($className, $rawContext);
        }

        return $rawContext;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $rawContext
     *
     * @return array<string, mixed>
     */
    private function addPropertyNames(string $className, array $rawContext): array
    {
        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Name::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Name $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());
                $rawContext['symfony']['marshal']['property_name'][$propertyIdentifier] = $attributeInstance->name;

                break;
            }
        }

        return $rawContext;
    }
}
