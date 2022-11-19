<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\TemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @internal
 */
final class JsonTemplateGenerator extends TemplateGenerator
{
    private readonly JsonScalarTemplateGenerator $scalarGenerator;
    private readonly JsonObjectTemplateGenerator $objectGenerator;
    private readonly JsonListTemplateGenerator $listGenerator;
    private readonly JsonDictTemplateGenerator $dictGenerator;

    public function __construct()
    {
        parent::__construct();

        $this->scalarGenerator = new JsonScalarTemplateGenerator();
        $this->objectGenerator = new JsonObjectTemplateGenerator($this);
        $this->listGenerator = new JsonListTemplateGenerator($this);
        $this->dictGenerator = new JsonDictTemplateGenerator($this);
    }

    public static function format(): string
    {
        return 'json';
    }

    protected function generateScalar(Type $type, string $accessor, array $context): string
    {
        return $this->scalarGenerator->generate($type, $accessor, $context);
    }

    protected function generateObject(Type $type, string $accessor, array $context): string
    {
        return $this->objectGenerator->generate($type, $accessor, $context);
    }

    protected function generateList(Type $type, string $accessor, array $context): string
    {
        return $this->listGenerator->generate($type, $accessor, $context);
    }

    protected function generateDict(Type $type, string $accessor, array $context): string
    {
        return $this->dictGenerator->generate($type, $accessor, $context);
    }

    protected function generateNull(array $context): string
    {
        return $this->fwrite("'null'", $context);
    }
}
