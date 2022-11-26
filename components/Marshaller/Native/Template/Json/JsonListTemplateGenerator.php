<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\ListTemplateGenerator;

/**
 * @internal
 */
final class JsonListTemplateGenerator extends ListTemplateGenerator
{
    protected function beforeItems(): string
    {
        return '[';
    }

    protected function afterItems(): string
    {
        return ']';
    }

    protected function itemSeparator(): string
    {
        return ',';
    }
}
