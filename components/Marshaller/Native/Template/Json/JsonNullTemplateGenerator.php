<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\NullTemplateGenerator;

/**
 * @internal
 */
final class JsonNullTemplateGenerator extends NullTemplateGenerator
{
    protected function null(array $context): string
    {
        return $this->fwrite("'null'", $context);
    }
}
