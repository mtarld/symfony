<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Json;

use Symfony\Component\Marshaller\Template\ListTemplateGenerator;

/**
 * @internal
 */
final class JsonListTemplateGenerator extends ListTemplateGenerator
{
    protected function beforeValues(): string
    {
        return '[';
    }

    protected function afterValues(): string
    {
        return ']';
    }

    protected function valueSeparator(): string
    {
        return ',';
    }
}
