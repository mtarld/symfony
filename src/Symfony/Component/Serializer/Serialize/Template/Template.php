<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Template;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\Serialize\Dom\DomTreeBuilderInterface;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Compiler;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ArgumentsNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ClosureNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ExpressionNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\PhpDocNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\Node\ReturnNode;
use Symfony\Component\Serializer\Serialize\TemplateGenerator\TemplateGeneratorInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
// TODO better name
final class Template
{
    /**
     * @param array<string, TemplateGeneratorInterface> $templateGenerators
     */
    public function __construct(
        private readonly DomTreeBuilderInterface $domTreeBuilder,
        private readonly array $templateGenerators,
        private readonly string $templateCacheDir,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function path(Type $type, string $format, array $context): string
    {
        $hash = hash('xxh128', (string) $type);

        if ([] !== $variant = $this->variant($context)) {
            usort($variant, fn (TemplateVariation $a, TemplateVariation $b): int => $a->compare($b));

            $hash .= '.'.hash('xxh128', implode('_', array_map(fn (TemplateVariation $t): string => (string) $t, $variant)));
        }

        return sprintf('%s%s%s.%s.php', $this->templateCacheDir, \DIRECTORY_SEPARATOR, $hash, $format);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function content(Type $type, string $format, array $context): string
    {
        $context['type'] = $type;

        /** @var TemplateGeneratorInterface|null $templateGenerator */
        $templateGenerator = $this->templateGenerators[$format] ?? null;
        if (null === $templateGenerator) {
            throw new UnsupportedException(sprintf('"%s" format is not supported.', $format));
        }

        $compiler = new Compiler();

        $compiler->compile(new PhpDocNode([sprintf('@param %s $data', $type), '@param resource $resource']));
        $phpDoc = $compiler->source();
        $compiler->reset();

        $argumentsNode = new ArgumentsNode(['data' => 'mixed', 'resource' => 'mixed', 'context' => 'array']);

        $compiler->indent();
        $bodyNodes = $templateGenerator->generate($this->domTreeBuilder->build($type, '$data', $context), $context);
        $compiler->outdent();

        $compiler->compile(new ExpressionNode(new ReturnNode(new ClosureNode($argumentsNode, 'void', true, $bodyNodes))));
        $php = $compiler->source();

        return "<?php\n\n".$phpDoc.$php;
    }

    /**
     * @param class-string $className
     *
     * @return list<list<TemplateVariation>>
     */
    public function classVariants(string $className): array
    {
        $groups = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (Groups::class !== $attribute->getName()) {
                    continue;
                }

                /** @var Groups $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                array_push($groups, ...$attributeInstance->groups);
            }
        }

        $groups = array_values(array_unique($groups));

        $variations = array_map(fn (string $g): TemplateVariation => TemplateVariation::createGroup($g), $groups);

        return $this->cartesianProduct($variations);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return list<TemplateVariation>
     */
    private function variant(array $context): array
    {
        $variant = [];
        foreach ($context['groups'] ?? [] as $group) {
            $variant[] = TemplateVariation::createGroup($group);
        }

        return $variant;
    }

    /**
     * @template T of mixed
     *
     * @param list<T> $variations
     *
     * @return list<list<T>>
     */
    private function cartesianProduct(array $variations): array
    {
        $variants = [[]];

        foreach ($variations as $variation) {
            foreach ($variants as $variant) {
                $variants[] = array_merge([$variation], $variant);
            }
        }

        return $variants;
    }
}
