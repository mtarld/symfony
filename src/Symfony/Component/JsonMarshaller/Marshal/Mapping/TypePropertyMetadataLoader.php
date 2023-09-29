<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Marshal\Mapping;

use Symfony\Component\JsonMarshaller\MarshallerInterface;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\JsonMarshaller\Type\TypeExtractorInterface;
use Symfony\Component\JsonMarshaller\Type\TypeGenericsHelper;

/**
 * Enhances properties marshalling metadata based on properties' type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type MarshalConfig from MarshallerInterface
 */
final class TypePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    private readonly TypeGenericsHelper $typeGenericsHelper;

    public function __construct(
        private readonly PropertyMetadataLoaderInterface $decorated,
        TypeExtractorInterface $typeExtractor,
    ) {
        $this->typeGenericsHelper = new TypeGenericsHelper($typeExtractor);
    }

    public function load(string $className, array $config, array $context): array
    {
        $result = $this->decorated->load($className, $config, $context);
        $genericTypes = $this->typeGenericsHelper->classGenericTypes($className, $context['original_type']);

        foreach ($result as &$metadata) {
            $type = $metadata->type();

            if (isset($genericTypes[(string) $type])) {
                $metadata = $metadata->withType($this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes));
                $type = $metadata->type();
            }

            if ($type->isObject() && $type->hasClass() && is_a($type->className(), \DateTimeInterface::class, true)) {
                $metadata = $metadata
                    ->withType(Type::string())
                    ->withFormatter(self::castDateTimeToString(...));
            }
        }

        return $result;
    }

    /**
     * @param MarshalConfig $config
     */
    public static function castDateTimeToString(\DateTimeInterface $dateTime, array $config): string
    {
        return $dateTime->format($config['date_time_format'] ?? \DateTimeInterface::RFC3339);
    }
}
