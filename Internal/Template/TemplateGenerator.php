<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Internal\Template;

use Symfony\Component\Marshaller\Exception\CircularReferenceException;
use Symfony\Component\Marshaller\Exception\UnsupportedTypeException;
use Symfony\Component\Marshaller\Internal\Ast\Compiler;
use Symfony\Component\Marshaller\Internal\Ast\Node\BinaryNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\IfNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Internal\Ast\Node\RawNode;
use Symfony\Component\Marshaller\Internal\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Internal\Hook\HookExtractor;
use Symfony\Component\Marshaller\Internal\Type\Type;
use Symfony\Component\Marshaller\Internal\Type\UnionType;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
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
                $this->generateTypeTemplate(TypeFactory::createFromString('null'), new ScalarNode(null), $context),
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
        // TODO extract now only for object
        if (null !== $hook = $this->hookExtractor->extractFromObject($type, $context)) {
            $hookResult = $hook((string) $type, (new Compiler())->compile($accessor)->source(), $context);

            $type = isset($hookResult['type']) ? TypeFactory::createFromString($hookResult['type']) : $type;
            $accessor = isset($hookResult['accessor']) ? new RawNode($hookResult['accessor']) : $accessor;
            $context = $hookResult['context'] ?? $context;
        }

        return match (true) {
            $type instanceof UnionType => $this->unionGenerator->generate($type, $accessor, $context),
            $type->isScalar(), $type->isNull() => $this->scalarGenerator->generate($type, $accessor, $context),
            $type->isObject() => $this->generateObjectTemplate($type, $accessor, $context),
            $type->isList() => $this->listGenerator->generate($type, $accessor, $context, $this),
            $type->isDict() => $this->dictGenerator->generate($type, $accessor, $context, $this),
            default => throw new UnsupportedTypeException((string) $type),
        };
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
            throw new CircularReferenceException($className);
        }

        $context['generated_classes'][$className] = true;

        return $this->objectGenerator->generate($type, $accessor, $context, $this);
    }
}
