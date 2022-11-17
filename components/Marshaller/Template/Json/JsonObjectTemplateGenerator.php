<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Json;

use Symfony\Component\Marshaller\Template\ObjectTemplateGenerator;

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

    protected function propertyName(string $name): string
    {
        return sprintf('%s:', json_encode($name));
    }

    protected function null(): string
    {
        return 'null';
    }
}
