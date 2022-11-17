<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template;

use Symfony\Component\Marshaller\Type\Type;

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

        $template = '';

        if ($context['validate_data']) {
            $template .= $this->writeLine(sprintf('if (!(%s)) {', $type->validator($accessor)), $context);
            ++$context['indentation_level'];

            $template .= $this->writeLine(sprintf("throw new \UnexpectedValueException('Invalid \"%s\" type');", $accessor), $context);
            --$context['indentation_level'];

            $template .= $this->writeLine('}', $context);
        }

        $context['readable_accessor'] = sprintf('%s[]', $context['readable_accessor']);

        $template .= $this->fwrite(sprintf("'%s'", addslashes($this->beforeValues())), $context)
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
