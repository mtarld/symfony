<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template\Json;

use Symfony\Polyfill\Marshaller\Metadata\Type;
use Symfony\Polyfill\Marshaller\Template\ScalarGenerator;

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
