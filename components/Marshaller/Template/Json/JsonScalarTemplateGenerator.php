<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Json;

use Symfony\Component\Marshaller\Template\ScalarGenerator;
use Symfony\Component\Marshaller\Type\Type;

/**
 * @internal
 */
final class JsonScalarTemplateGenerator extends ScalarGenerator
{
    protected function scalar(Type $type, string $accessor, array $context): string
    {
        return $this->fwrite("json_encode($accessor)", $context);
    }
}
