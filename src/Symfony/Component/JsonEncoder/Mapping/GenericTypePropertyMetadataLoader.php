<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Mapping;

use Symfony\Component\TypeInfo\Context\TypeGenericsHelper;

/**
 * Enhances properties encoding/decoding metadata based on properties' generic type.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class GenericTypePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    private TypeGenericsHelper $typeGenericsHelper;

    public function __construct(
        private PropertyMetadataLoaderInterface $decorated,
    ) {
        $this->typeGenericsHelper = new TypeGenericsHelper();
    }

    public function load(string $className, array $config, array $context): array
    {
        $result = $this->decorated->load($className, $config, $context);
        $genericTypes = $this->typeGenericsHelper->getClassGenericTypes($className, $context['original_type']);

        foreach ($result as &$metadata) {
            $type = $metadata->type;

            if (isset($genericTypes[(string) $type])) {
                $metadata = $metadata->withType($this->typeGenericsHelper->replaceGenericTypes($type, $genericTypes));
            }
        }

        return $result;
    }
}
