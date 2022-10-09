<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template\Generator\Json;

use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use Symfony\Component\Marshaller\Metadata\Attribute\FormatterAttribute;
use Symfony\Component\Marshaller\Metadata\PropertyMetadata;
use Symfony\Component\Marshaller\Metadata\ValueMetadata;
use Symfony\Component\Marshaller\Template\Generator\OutputWriterTrait;
use Symfony\Component\Marshaller\Template\Generator\ValueGeneratorInterface;
use Symfony\Component\Marshaller\Template\Generator\ValueGenerators;

final class JsonObjectValueGenerator implements ValueGeneratorInterface
{
    use OutputWriterTrait;

    public function __construct(
        private readonly ValueGenerators $valueGenerators,
    ) {
    }

    public function generate(ValueMetadata $value, Expr $accessor): array
    {
        $objectName = uniqid('o');
        $reflectionName = uniqid('r');

        $statements = [
            new Stmt\Expression(new Expr\Assign(new Expr\Variable($objectName), $accessor)),
            new Stmt\Expression(new Expr\Assign(new Expr\Variable($reflectionName), new Expr\New_(new Name\FullyQualified(\ReflectionClass::class), [new Expr\Variable($objectName)]))),
        ];

        $statements[] = $this->write('{');

        $properties = new \ArrayIterator($value->class()->properties);

        $valid = $properties->valid();
        while ($valid) {
            array_push($statements, ...$this->generateProperty(
                $properties->current(),
                $objectName,
                $reflectionName,
            ));

            $properties->next();
            $valid = $properties->valid();
            if ($valid) {
                $statements[] = $this->write(',');
            }
        }

        $statements[] = new Stmt\Unset_([new Expr\Variable($objectName), new Expr\Variable($reflectionName)]);
        $statements[] = $this->write('}');

        if (!$value->isNullable()) {
            return $statements;
        }

        return [
            new Stmt\If_(new Expr\BinaryOp\Identical(new Expr\ConstFetch(new Name('null')), $accessor), [
                'stmts' => [
                    $this->write('null'),
                ],
                'else' => new Stmt\Else_($statements),
            ]),
        ];
    }

    public function canGenerate(ValueMetadata $value): bool
    {
        return $value->isObject();
    }

    /**
     * @return array<Stmt>
     */
    private function generateProperty(PropertyMetadata $property, string $objectName, string $reflectionName): array
    {
        $publicAccessor = new Expr\PropertyFetch(new Expr\Variable($objectName), $property->name);

        $reflectionAccessor = new Expr\MethodCall(
            new Expr\MethodCall(new Expr\Variable($reflectionName), 'getProperty', [new Scalar\String_($property->name)]),
            'getValue',
            [new Expr\Variable($objectName)],
        );

        $accessor = $property->isPublic ? $publicAccessor : $reflectionAccessor;

        if ($property->attributes->has(FormatterAttribute::class)) {
            $class = $property->attributes->get(FormatterAttribute::class)->class;
            $method = $property->attributes->get(FormatterAttribute::class)->method;

            $accessor = new Expr\StaticCall(new Name\FullyQualified($class), $method, [$accessor, new Expr\Variable('context')]);
        }

        return [
            $this->write(new Expr\FuncCall(new Name('json_encode'), [new Scalar\String_($property->convertedName)])),
            $this->write(':'),
            ...$this->valueGenerators->for($property->value)->generate($property->value, $accessor),
        ];
    }
}
