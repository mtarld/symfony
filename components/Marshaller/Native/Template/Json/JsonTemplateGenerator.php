<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template\Json;

use Symfony\Component\Marshaller\Native\Template\TemplateGenerator;
use Symfony\Component\Marshaller\Native\Template\UnionTemplateGenerator;

/**
 * @internal
 */
final class JsonTemplateGenerator extends TemplateGenerator
{
    public function __construct()
    {
        parent::__construct(
            scalarGenerator: new JsonScalarTemplateGenerator(),
            nullGenerator: new JsonNullTemplateGenerator(),
            objectGenerator: new JsonObjectTemplateGenerator($this),
            listGenerator: new JsonListTemplateGenerator($this),
            dictGenerator: new JsonDictTemplateGenerator($this),
            unionGenerator: new UnionTemplateGenerator($this),
            format: 'json',
        );
    }
}
