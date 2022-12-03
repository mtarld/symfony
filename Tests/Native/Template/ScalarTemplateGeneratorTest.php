<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\NodeInterface;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Ast\Node\VariableNode;
use Symfony\Component\Marshaller\Native\Template\ScalarTemplateGenerator;
use Symfony\Component\Marshaller\Native\Type\Type;

final class ScalarTemplateGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $scalarTemplateGenerator = new class () extends ScalarTemplateGenerator {
            protected function scalar(Type $type, NodeInterface $accessor, array $context): array
            {
                return [new ScalarNode('SCALAR')];
            }
        };

        $this->assertEquals([new ScalarNode('SCALAR')], $scalarTemplateGenerator->generate(new Type('int'), new VariableNode('accessor'), []));
    }
}
