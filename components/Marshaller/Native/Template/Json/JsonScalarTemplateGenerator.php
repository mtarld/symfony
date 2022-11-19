<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\ScalarGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @internal
 */
final class JsonScalarTemplateGenerator extends ScalarGenerator
{
    protected function scalar(Type $type, string $accessor, array $context): string
    {
        if ('string' === $type->name()) {
            return $this->fwrite("'\"'", $context)
                .$this->fwrite($accessor, $context)
                .$this->fwrite("'\"'", $context);
        }

        return $this->fwrite($accessor, $context);
    }
}
