<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @internal
 */
abstract class ScalarGenerator
{
    use PhpWriterTrait;

    abstract protected function scalar(Type $type, string $accessor, array $context): string;

    final public function generate(Type $type, string $accessor, array $context): string
    {
        return $this->scalar($type, $accessor, $context);
    }
}
