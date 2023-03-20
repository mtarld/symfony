<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Mapping\Encode;

use Symfony\Component\Encoder\EncoderInterface;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * Casts DateTime properties to string properties.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 *
 * @phpstan-import-type EncodeConfig from EncoderInterface
 */
final readonly class DateTimeTypePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private PropertyMetadataLoaderInterface $decorated,
    ) {
    }

    /**
     * @param EncodeConfig $config
     */
    public function load(string $className, array $config, array $context): array
    {
        $result = $this->decorated->load($className, $config, $context);

        foreach ($result as &$metadata) {
            $type = $metadata->getType();

            if ($type->isObject() && is_a($type->getClassName(), \DateTimeInterface::class, true)) {
                $metadata = $metadata
                    ->withType(Type::string())
                    ->withFormatter(self::castDateTimeToString(...));
            }
        }

        return $result;
    }

    /**
     * @param EncodeConfig $config
     */
    public static function castDateTimeToString(\DateTimeInterface $dateTime, array $config): string
    {
        return $dateTime->format($config['date_time_format'] ?? \DateTimeInterface::RFC3339);
    }
}
