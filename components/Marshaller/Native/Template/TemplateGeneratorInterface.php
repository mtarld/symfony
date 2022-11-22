<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Native\Type\UnionType;

/**
 * @internal
 */
interface TemplateGeneratorInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function generate(Type|UnionType $type, string $accessor, array $context): string;

    public function format(): string;
}
