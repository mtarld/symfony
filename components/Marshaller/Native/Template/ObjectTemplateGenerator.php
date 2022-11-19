<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Hook\HookExtractor;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Type\ReflectionTypeExtractor;

/**
 * @internal
 */
abstract class ObjectTemplateGenerator
{
    use PhpWriterTrait;
    use VariableNameScoperTrait;

    private readonly HookExtractor $hookExtractor;
    private readonly ReflectionTypeExtractor $reflectionTypeExtractor;

    public function __construct(
        private readonly TemplateGenerator $templateGenerator,
    ) {
        $this->hookExtractor = new HookExtractor();
        $this->reflectionTypeExtractor = new ReflectionTypeExtractor();
    }

    abstract protected function beforeProperties(): string;

    abstract protected function afterProperties(): string;

    abstract protected function propertySeparator(): string;

    abstract protected function beforePropertyName(): string;

    abstract protected function afterPropertyName(): string;

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(Type $type, string $accessor, array $context): string
    {
        $class = new \ReflectionClass($type->className());

        $objectName = $this->scopeVariableName('object', $context);

        $template = $this->writeLine("$objectName = $accessor;", $context)
            .$this->fwrite(sprintf("'%s'", $this->beforeProperties()), $context);

        if ($context['validate_data']) {
            $template .= $this->writeLine(sprintf('if (!(%s)) {', $type->validator($objectName)), $context);
            ++$context['indentation_level'];

            $template .= $this->writeLine(sprintf("throw new \UnexpectedValueException('Invalid \"%s\" type');", $context['readable_accessor']), $context);
            --$context['indentation_level'];

            $template .= $this->writeLine('}', $context);
        }

        $properties = $class->getProperties();
        $propertySeparator = '';

        $currentAccessor = $context['readable_accessor'];

        foreach ($properties as $i => $property) {
            $context['readable_accessor'] = sprintf('%s::$%s', $currentAccessor, $property->getName());

            $propertyAccessor = sprintf('%s->%s', $objectName, $property->getName());

            $template .= $this->fwrite(sprintf("'%s'", $propertySeparator), $context);

            if (null !== $hook = $this->hookExtractor->extractFromProperty($property, $context)) {
                $hookContext = $context + [
                    'property_name_template_generator' => $this->generatePropertyName(...),
                    'property_value_template_generator' => function (string $type, string $accessor, array $context): string {
                        return $this->templateGenerator->generate(Type::createFromString($type), $accessor, $context);
                    },
                ];

                if (null !== $hookResult = $hook($property, $propertyAccessor, $this->templateGenerator->format(), $hookContext)) {
                    $template .= $hookResult;
                    $propertySeparator = $this->propertySeparator();

                    continue;
                }
            }

            if (!$property->isPublic()) {
                throw new \RuntimeException(sprintf('"%s::$%s" must be public', $class->getName(), $property->getName()));
            }

            $template .= $this->generatePropertyName(sprintf("'%s'", $property->getName()), $context);
            $template .= $this->templateGenerator->generate(
                Type::createFromString($this->reflectionTypeExtractor->extractFromProperty($property)),
                $propertyAccessor,
                $context,
            );

            $propertySeparator = $this->propertySeparator();
        }

        $template .= $this->fwrite(sprintf("'%s'", $this->afterProperties()), $context);

        return $template;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function generatePropertyName(string $name, array $context): string
    {
        return $this->fwrite(sprintf("'%s'", $this->beforePropertyName()), $context)
            .$this->fwrite($name, $context)
            .$this->fwrite(sprintf("'%s'", $this->afterPropertyName()), $context);
    }
}
