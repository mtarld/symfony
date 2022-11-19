<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\ObjectTemplateGenerator;

/**
 * @internal
 */
final class JsonObjectTemplateGenerator extends ObjectTemplateGenerator
{
    protected function beforeProperties(): string
    {
        return '{';
    }

    protected function afterProperties(): string
    {
        return '}';
    }

    protected function propertySeparator(): string
    {
        return ',';
    }

    protected function beforePropertyName(): string
    {
        return '"';
    }

    protected function afterPropertyName(): string
    {
        return '":';
    }

    protected function null(): string
    {
        return 'null';
    }
}
