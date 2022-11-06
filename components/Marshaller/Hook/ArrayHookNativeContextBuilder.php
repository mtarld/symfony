<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Hook;

use Symfony\Component\Marshaller\Type\TypeExtractor;

final class ArrayHookNativeContextBuilder
{
    /**
     * @param array<string, mixed>
     *
     * @return array<string, mixed>
     */
    public function build(string $format, array $context): array
    {
        $typeExtractor = new TypeExtractor();

        $valueGenerator = match ($format) {
            'json' => ValueTemplateGenerator::generateByType(...),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" format', $format)),
        };

        $context['hooks']['array'] = static function (\ReflectionProperty $property, string $objectAccessor, array $context) use ($typeExtractor, $valueGenerator): string {
            $types = $typeExtractor->extract($property);

            // TODO check type consistency (dict, list, value kind)
            $value = '';

            // TODO handle null

            if ('' === $value) {
                return $value;
            }

            return $context['propertyNameGenerator']($property, $context).$value;
        };

        return $context;
    }
}
