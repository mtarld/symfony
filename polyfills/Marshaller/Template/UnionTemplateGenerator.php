<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

use Symfony\Polyfill\Marshaller\Metadata\UnionType;

/**
 * @internal
 */
final class UnionTemplateGenerator
{
    use PhpWriterTrait;
    use VariableNameScoperTrait;

    public function __construct(
        private readonly TemplateGenerator $templateGenerator,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(UnionType $type, string $accessor, array $context): string
    {
        $template = '';
        $typesCount = count($type);

        // TODO narrowest class
        $sortedTypes = $type->types;

        foreach ($sortedTypes as $i => $type) {
            $ifStructure = sprintf('} elseif (%s) {', $type->validator($accessor));

            if (0 === $i) {
                $ifStructure = sprintf('if (%s) {', $type->validator($accessor));
            } elseif ($typesCount - 1 === $i && !$context['validate_data']) {
                $ifStructure = '} else {';
            }

            $template .= $this->writeLine($ifStructure, $context);
            ++$context['indentation_level'];

            $template .= $this->templateGenerator->generate($type, $accessor, $context);
            --$context['indentation_level'];
        }

        if ($context['validate_data']) {
            $template .= $this->writeLine('} else {', $context);
            ++$context['indentation_level'];

            $template .= $this->writeLine("throw new \UnexpectedValueException(sprintf('Invalid \"%s\" type', '$accessor'));", $context);
            --$context['indentation_level'];
        }

        $template .= $this->writeLine('}', $context);

        return $template;
    }
}
