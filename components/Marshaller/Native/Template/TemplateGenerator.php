<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Hook\HookExtractor;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Native\Type\UnionType;

/**
 * @internal
 */
abstract class TemplateGenerator implements TemplateGeneratorInterface
{
    use PhpWriterTrait;

    private readonly HookExtractor $hookExtractor;

    public function __construct(
        private readonly ScalarTemplateGenerator $scalarGenerator,
        private readonly NullTemplateGenerator $nullGenerator,
        private readonly ObjectTemplateGenerator $objectGenerator,
        private readonly ListTemplateGenerator $listGenerator,
        private readonly DictTemplateGenerator $dictGenerator,
        private readonly UnionTemplateGenerator $unionGenerator,
        private readonly string $format,
    ) {
        $this->hookExtractor = new HookExtractor();
    }

    public function format(): string
    {
        return $this->format;
    }

    public function generate(Type|UnionType $type, string $accessor, array $context): string
    {
        $template = '';

        if ($type->isNullable()) {
            $template .= $this->writeLine("if (null === $accessor) {", $context);

            ++$context['indentation_level'];

            $template .= $this->generateTypeTemplate(new Type('null'), 'NO_ACCESSOR', $context);

            --$context['indentation_level'];
            $template .= $this->writeLine('} else {', $context);

            ++$context['indentation_level'];
        }

        $template .= $this->generateTypeTemplate($type, $accessor, $context);

        if ($type->isNullable()) {
            --$context['indentation_level'];
            $template .= $this->writeLine('}', $context);
        }

        --$context['indentation_level'];

        return $template;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function generateTypeTemplate(Type|UnionType $type, string $accessor, array $context): string
    {
        if ($type instanceof UnionType) {
            return $this->unionGenerator->generate($type, $accessor, $context);
        }

        $valueTemplateGenerator = function (Type $type, string $accessor, array $context): string {
            return match (true) {
                $type->isNull() => $this->nullGenerator->generate($context),
                $type->isScalar() => $this->scalarGenerator->generate($type, $accessor, $context),
                $type->isObject() => $this->generateObjectTemplate($type, $accessor, $context),
                $type->isList() => $this->listGenerator->generate($type, $accessor, $context),
                $type->isDict() => $this->dictGenerator->generate($type, $accessor, $context),
                default => throw new \InvalidArgumentException(sprintf('Unkown "%s" type.', (string) $type)),
            };
        };

        if (null !== $hook = $this->hookExtractor->extractFromType($type, $context)) {
            $hookContext = $context + [
                'type_value_template_generator' => static function (string $type, string $accessor, array $context) use ($valueTemplateGenerator): string {
                    return $valueTemplateGenerator(Type::createFromString($type), $accessor, $context);
                },
            ];

            if (null !== $hookResult = $hook((string) $type, $accessor, $this->format, $hookContext)) {
                return $hookResult;
            }
        }

        return $valueTemplateGenerator($type, $accessor, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function generateObjectTemplate(Type $type, string $accessor, array $context): string
    {
        $className = $type->className();

        if (isset($context['generated_classes'][$className])) {
            throw new \RuntimeException(sprintf('Circular reference detected on "%s" detected.', $className));
        }

        $context['generated_classes'][$className] = true;

        return $this->objectGenerator->generate($type, $accessor, $context);
    }
}
