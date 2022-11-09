<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template\Json;

use Symfony\Polyfill\Marshaller\Metadata\Type;
use Symfony\Polyfill\Marshaller\Template\ScalarTemplateGenerator;

final class JsonScalarTemplateGenerator extends ScalarTemplateGenerator
{
    protected function generateValue(Type $type, string $accessor, array $context): string
    {
        return $this->fwrite("json_encode($accessor)", $context);
    }
}
