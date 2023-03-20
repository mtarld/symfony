<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Mapping\Decode;

use Symfony\Component\Encoder\DecoderInterface;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * Casts DateTime properties to string properties.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type DecodeConfig from DecoderInterface
 */
final readonly class DateTimeTypePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private PropertyMetadataLoaderInterface $decorated,
    ) {
    }

    /**
     * @param DecodeConfig $config
     */
    public function load(string $className, array $config, array $context): array
    {
        $result = $this->decorated->load($className, $config, $context);

        foreach ($result as &$metadata) {
            $type = $metadata->getType();

            if ($type->isObject() && is_a($type->getClassName(), \DateTimeInterface::class, true)) {
                $metadata = $metadata
                    ->withType(Type::string())
                    ->withFormatter(self::castStringToDateTime(...));
            }
        }

        return $result;
    }

    /**
     * @param DecodeConfig $config
     */
    public static function castStringToDateTime(string $string, array $config): \DateTimeInterface
    {
        if (false !== $dateTime = \DateTimeImmutable::createFromFormat($config['date_time_format'] ?? \DateTimeInterface::RFC3339, $string)) {
            return $dateTime;
        }

        return new \DateTimeImmutable($string);
    }
}
