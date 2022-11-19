<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\DictTemplateGenerator;

/**
 * @internal
 */
final class JsonDictTemplateGenerator extends DictTemplateGenerator
{
    protected function beforeValues(): string
    {
        return '{';
    }

    protected function afterValues(): string
    {
        return '}';
    }

    protected function valueSeparator(): string
    {
        return ',';
    }

    protected function keyName(string $name): string
    {
        return "json_encode($name).':'";
    }
}
