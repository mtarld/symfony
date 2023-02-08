<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Context\ContextBuilder\Unmarshal;

use Symfony\Component\Marshaller\Attribute\Formatter;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\ContextBuilder\UnmarshalContextBuilderInterface;
use Symfony\Component\Marshaller\MarshallableResolverInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class FormatterAttributeContextBuilder implements UnmarshalContextBuilderInterface
{
    public function __construct(
        private readonly MarshallableResolverInterface $marshallableResolver,
    ) {
    }

    public function build(string $type, Context $context, array $rawContext): array
    {
        foreach ($this->marshallableResolver->resolve() as $className => $_) {
            $rawContext = $this->addPropertyFormatters($className, $rawContext);
        }

        return $rawContext;
    }

    /**
     * @param class-string         $className
     * @param array<string, mixed> $rawContext
     *
     * @return array<string, mixed>
     */
    private function addPropertyFormatters(string $className, array $rawContext): array
    {
        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Formatter::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Formatter $attributeInstance */
                $attributeInstance = $attribute->newInstance();
                if (null === $attributeInstance->unmarshal) {
                    break;
                }

                $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());
                $rawContext['_symfony']['unmarshal']['property_formatter'][$propertyIdentifier] = $attributeInstance->unmarshal;

                break;
            }
        }

        return $rawContext;
    }
}
