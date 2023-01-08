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

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class FormatterAttributeContextBuilder implements UnmarshalContextBuilderInterface
{
    public function build(string $type, Context $context, array $rawContext): array
    {
        if (!class_exists($type)) {
            return $rawContext;
        }

        foreach ((new \ReflectionClass($type))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Formatter::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Formatter $attributeInstance */
                $attributeInstance = $attribute->newInstance();
                if (null === $attributeInstance->unmarshalFormatter) {
                    break;
                }

                $propertyIdentifier = sprintf('%s::$%s', $property->getDeclaringClass()->getName(), $property->getName());
                $rawContext['symfony']['unmarshal']['property_formatter'][$propertyIdentifier] = $attributeInstance->unmarshalFormatter;

                break;
            }
        }

        return $rawContext;
    }
}
