<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template\Json;

use Symfony\Polyfill\Marshaller\Template\ListTemplateGenerator;

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
