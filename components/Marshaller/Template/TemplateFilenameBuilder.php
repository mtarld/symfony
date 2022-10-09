<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template;

use Symfony\Component\Marshaller\Context\Context;

final class TemplateFilenameBuilder
{
    public function build(\ReflectionClass $class, Context $context): string
    {
        return sprintf('%s_%s', md5($class->getName()), md5($context->signature()));
    }
}
