<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Native\Template;

use Symfony\Component\Marshaller\Native\Ast\Compiler;
use Symfony\Component\Marshaller\Native\Ast\Node\BinaryNode;
use Symfony\Component\Marshaller\Native\Ast\Node\IfNode;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Hook\HookExtractor;
use Symfony\Component\Marshaller\Native\Type\Type;
use Symfony\Component\Marshaller\Native\Type\UnionType;

/**
 * @internal
 */
final class TemplateGenerator
{
    private readonly HookExtractor $hookExtractor;
    private readonly UnionTemplateGenerator $unionGenerator;

    public function __construct(
        private readonly ScalarTemplateGenerator $scalarGenerator,
        private readonly ObjectTemplateGenerator $objectGenerator,
        private readonly ListTemplateGenerator $listGenerator,
        private readonly DictTemplateGenerator $dictGenerator,
    ) {
        $this->hookExtractor = new HookExtractor();
        $this->unionGenerator = new UnionTemplateGenerator($this);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    public function generate(Type|UnionType $type, NodeInterface $accessor, array $context): array
    {
        $nodes = $this->generateTypeTemplate($type, $accessor, $context);

        if (!$type->isNullable()) {
            return $nodes;
        }

        return [
            new IfNode(
                new BinaryNode('===', new ScalarNode(null), $accessor),
                $this->generateTypeTemplate(new Type('null'), new ScalarNode(null), $context),
                $nodes,
            ),
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    private function generateTypeTemplate(Type|UnionType $type, NodeInterface $accessor, array $context): array
    {
        if ($type instanceof UnionType) {
            return $this->unionGenerator->generate($type, $accessor, $context);
        }

        if (null !== $hook = $this->hookExtractor->extractFromType($type, $context)) {
            [$type, $accessor, $context] = $this->callTypeHook($hook, $type, $accessor, $context);
        }

        return match (true) {
            $type instanceof UnionType => $this->unionGenerator->generate($type, $accessor, $context),
            $type->isScalar(), $type->isNull() => $this->scalarGenerator->generate($type, $accessor, $context),
            $type->isObject() => $this->generateObjectTemplate($type, $accessor, $context),
            $type->isList() => $this->listGenerator->generate($type, $accessor, $context, $this),
            $type->isDict() => $this->dictGenerator->generate($type, $accessor, $context, $this),
            default => throw new \InvalidArgumentException(sprintf('Unknown "%s" type.', (string) $type)),
        };
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array{0: Type|UnionType, 1: NodeInterface, 2: array<string, mixed>}
     */
    private function callTypeHook(callable $hook, Type $type, NodeInterface $accessor, array $context): array
    {
        $hookResult = $hook((string) $type, (new Compiler())->compile($accessor)->source(), $context);

        if (!isset($hookResult['type'])) {
            throw new \RuntimeException('Hook array result is missing "type".');
        }

        if (!is_string($hookResult['type'])) {
            throw new \RuntimeException('Hook array result\'s "type" must be a "string".');
        }

        if (!isset($hookResult['accessor'])) {
            throw new \RuntimeException('Hook array result is missing "accessor".');
        }

        if (!is_string($hookResult['accessor'])) {
            throw new \RuntimeException('Hook array result\'s "accessor" must be a "string".');
        }

        if (!isset($hookResult['context'])) {
            throw new \RuntimeException('Hook array result is missing "context".');
        }

        if (!is_array($hookResult['context'])) {
            throw new \RuntimeException('Hook array result\'s "context" must be an "array".');
        }

        return [
            Type::createFromString($hookResult['type']),
            new RawNode($hookResult['accessor']),
            $hookResult['context'],
        ];
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<NodeInterface>
     */
    private function generateObjectTemplate(Type $type, NodeInterface $accessor, array $context): array
    {
        $className = $type->className();

        if (isset($context['generated_classes'][$className])) {
            throw new \RuntimeException(sprintf('Circular reference detected on "%s" detected.', $className));
        }

        $context['generated_classes'][$className] = true;

        return $this->objectGenerator->generate($type, $accessor, $context, $this);
    }
}
