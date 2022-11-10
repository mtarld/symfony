<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

use Symfony\Polyfill\Marshaller\Metadata\Type;

abstract class ListTemplateGenerator
{
    use PhpWriterTrait;
    use VariableNameScoperTrait;

    public function __construct(
        private readonly TemplateGenerator $templateGenerator,
    ) {
    }

    abstract protected function beforeValues(): string;

    abstract protected function afterValues(): string;

    abstract protected function valueSeparator(): string;

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(Type $type, string $accessor, array $context): string
    {
        $prefixName = $this->scopeVariableName('prefix', $context);
        $valueName = $this->scopeVariableName('value', $context);

        $template = $this->fwrite(sprintf("'%s'", addslashes($this->beforeValues())), $context)
            .$this->writeLine("$prefixName = '';", $context)
            .$this->writeLine("foreach ($accessor as $valueName) {", $context);

        ++$context['indentation_level'];

        $template .= $this->fwrite($prefixName, $context)
            .$this->templateGenerator->generate($type->collectionValueType(), $valueName, $context)
            .$this->writeLine(sprintf("$prefixName = '%s';", addslashes($this->valueSeparator())), $context);

        --$context['indentation_level'];

        $template .= $this->writeLine('}', $context)
            .$this->fwrite(sprintf("'%s'", addslashes($this->afterValues())), $context);

        return $template;
    }
}
