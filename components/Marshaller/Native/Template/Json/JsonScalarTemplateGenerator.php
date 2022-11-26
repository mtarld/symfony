<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\ScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @internal
 */
final class JsonScalarTemplateGenerator extends ScalarTemplateGenerator
{
    protected function scalar(Type $type, string $accessor, array $context): string
    {
        if ('string' === $type->name()) {
            return $this->fwrite("'\"'", $context)
                .$this->fwrite('addcslashes('.$accessor.', "\0\t\"\$\\\\")', $context)
                .$this->fwrite("'\"'", $context);
        }

        if ('bool' === $type->name()) {
            return $this->fwrite("$accessor ? 'true' : 'false'", $context);
        }

        return $this->fwrite($accessor, $context);
    }
}
