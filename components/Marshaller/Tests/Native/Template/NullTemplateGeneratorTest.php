<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Native\Ast\Node\ScalarNode;
use Symfony\Component\Marshaller\Native\Template\NullTemplateGenerator;

final class NullTemplateGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $nullTemplateGenerator = new class () extends NullTemplateGenerator {
            protected function null(array $context): array
            {
                return [new ScalarNode('NULL')];
            }
        };

        $this->assertEquals([new ScalarNode('NULL')], $nullTemplateGenerator->generate([]));
    }
}
