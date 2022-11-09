<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template\Json;

use Symfony\Polyfill\Marshaller\Template\ObjectTemplateGenerator;

/**
 * @internal
 */
final class JsonObjectTemplateGenerator extends ObjectTemplateGenerator
{
    protected function generateBeforeProperties(array $context): string
    {
        return $this->fwrite("'{'", $context);
    }

    protected function generateAfterProperties(array $context): string
    {
        return $this->fwrite("'}'", $context);
    }

    protected function getBeforePropertyString(bool $isFirst, bool $isLast): string
    {
        return $isFirst ? '' : ',';
    }

    protected function getAfterPropertyString(bool $isFirst, bool $isLast): string
    {
        return '';
    }

    protected function getPropertyNameString(string $propertyName): string
    {
        return sprintf('%s:', json_encode($propertyName));
    }

    protected function null(): string
    {
        return 'null';
    }
}
