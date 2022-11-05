<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

use Symfony\Component\Marshaller\Attribute\Name;

final class PropertyNameHookNativeContextBuilder
{
    /**
     * @param array<string, mixed>
     *
     * @return array<string, mixed>
     */
    public function build(\ReflectionClass $class, array $context): array
    {
        if (!isset($context['hooks'])) {
            $context['hooks'] = [];
        }

        $properties = $class->getProperties();
        foreach ($class->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Name::class !== $attribute->getName()) {
                    continue;
                }

                $context['hooks'][sprintf('%s::$%s', $class->getName(), $property->getName())] = $this->createHook($attribute->getArguments()[0]);
            }
        }

        return $context;
    }

    private function createHook(string $name): callable
    {
        return static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($name): string {
            $name = $context['fwrite'](sprintf("'%s%s:'", $context['prefix'], json_encode($name)), $context);
            $value = $context['propertyValueGenerator']($property, $objectAccessor, $context);

            return $name.$value;
        };
    }
}
