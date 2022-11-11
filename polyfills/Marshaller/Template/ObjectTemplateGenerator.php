<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

use Symfony\Polyfill\Marshaller\Metadata\HookExtractor;
use Symfony\Polyfill\Marshaller\Metadata\Type;
use Symfony\Polyfill\Marshaller\Metadata\TypeFactory;

/**
 * @internal
 */
abstract class ObjectTemplateGenerator
{
    use PhpWriterTrait;
    use VariableNameScoperTrait;

    private readonly HookExtractor $hookExtractor;

    public function __construct(
        private readonly TemplateGenerator $templateGenerator,
    ) {
        $this->hookExtractor = new HookExtractor();
    }

    abstract protected function beforeProperties(): string;

    abstract protected function afterProperties(): string;

    abstract protected function propertySeparator(): string;

    abstract protected function propertyName(string $name): string;

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(Type $type, string $accessor, array $context): string
    {
        $class = new \ReflectionClass($type->className());

        $objectName = $this->scopeVariableName('object', $context);

        $template = $this->writeLine("$objectName = $accessor;", $context)
            .$this->fwrite(sprintf("'%s'", addslashes($this->beforeProperties())), $context);

        $properties = $class->getProperties();
        $propertySeparator = '';

        foreach ($properties as $i => $property) {
            if (null !== $hook = $this->hookExtractor->extractFromProperty($property, $context)) {
                $hookContext = $context + [
                    'propertyNameGenerator' => $this->generatePropertyName(...),
                    'propertyValueGenerator' => $this->generatePropertyValue(...),
                ];

                $template .= $hook($property, $objectName, $hookContext);

                continue;
            }

            $template .= $this->generatePropertyName($property, $propertySeparator, $context)
                .$this->generatePropertyValue($property, $objectName, $context);

            $propertySeparator = $this->propertySeparator();
        }

        $template .= $this->fwrite(sprintf("'%s'", addslashes($this->afterProperties())), $context);

        return $template;
    }

     /**
      * @param array<string, mixed> $context
      */
     private function generatePropertyName(\ReflectionProperty $property, string $prefix, array $context): string
     {
         $name = $property->getName();
         foreach ($property->getAttributes() as $attribute) {
             if (\MarshalName::class !== $attribute->getName()) {
                 continue;
             }

             $name = $attribute->newInstance()->name;

             break;
         }

         $content = '' === $prefix
             ? $this->propertyName($name)
             : sprintf("'%s'.%s", $prefix, $this->propertyName($name))
         ;

         return $this->fwrite($content, $context);
     }

    /**
     * @param array<string, mixed> $context
     */
    private function generatePropertyValue(\ReflectionProperty $property, string $objectAccessor, array $context): string
    {
        $formatter = null;
        $reflectionType = $property->getType();
        $declaringClass = $property->getDeclaringClass();

        $propertyAccessor = sprintf('%s->%s', $objectAccessor, $property->getName());

        foreach ($property->getAttributes() as $attribute) {
            if (\MarshalFormatter::class !== $attribute->getName()) {
                continue;
            }

            $callable = $attribute->newInstance()->callable;

            $formatter = (new \ReflectionFunction(\Closure::fromCallable($callable)));
            $reflectionType = $formatter->getReturnType();
            $declaringClass = $formatter->getClosureScopeClass();

            $propertyAccessor = sprintf('%s(%s, $context)', $callable, $propertyAccessor);

            break;
        }

        $type = TypeFactory::createFromReflection($reflectionType, $declaringClass);

        return $this->templateGenerator->generate($type, $propertyAccessor, $context);
    }
}
