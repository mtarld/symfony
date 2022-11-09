<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template\Json;

use Symfony\Polyfill\Marshaller\Template\PhpWriterTrait;
use Symfony\Polyfill\Marshaller\Template\TemplateGeneratorInterface;
use Symfony\Polyfill\Marshaller\Metadata\Type;

final class JsonTemplateGenerator implements TemplateGeneratorInterface
{
    use PhpWriterTrait;

    private readonly JsonScalarTemplateGenerator $scalarGenerator;
    private readonly JsonObjectTemplateGenerator $objectGenerator;

    public function __construct(
    ) {
        $this->scalarGenerator = new JsonScalarTemplateGenerator();
        $this->objectGenerator = new JsonObjectTemplateGenerator($this);
    }

    public function generateNull(array $context): string
    {
        return $this->fwrite("'null'", $context);
    }

    public function generateScalar(Type $type, string $accessor, array $context): string
    {
        return $this->scalarGenerator->generate($type, $accessor, $context);
    }

    public function generateObject(Type $type, string $accessor, array $context): string
    {
        return $this->objectGenerator->generate($type, $accessor, $context);
    }
}
