<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Type\Type;

/**
 * @internal
 */
abstract class DictTemplateGenerator
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

    abstract protected function keyName(string $name): string;

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(Type $type, string $accessor, array $context): string
    {
        $prefixName = $this->scopeVariableName('prefix', $context);
        $keyName = $this->scopeVariableName('key', $context);
        $valueName = $this->scopeVariableName('value', $context);

        $template = $this->fwrite(sprintf("'%s'", addslashes($this->beforeValues())), $context)
            .$this->writeLine("$prefixName = '';", $context)
            .$this->writeLine("foreach ($accessor as $keyName => $valueName) {", $context);

        ++$context['indentation_level'];

        $template .= $this->fwrite(sprintf('%s.%s', $prefixName, $this->keyName($keyName)), $context)
            .$this->templateGenerator->generate($type->collectionValueType(), $valueName, $context)
            .$this->writeLine(sprintf("$prefixName = '%s';", addslashes($this->valueSeparator())), $context);

        --$context['indentation_level'];

        $template .= $this->writeLine('}', $context)
            .$this->fwrite(sprintf("'%s'", addslashes($this->afterValues())), $context);

        return $template;
    }
}
