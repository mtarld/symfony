<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Tests\Native\Template;

use Symfony\Component\Marshaller\Native\Template\VariableNameScoperTrait;

final class VariableNameScoperTest extends TemplateGeneratorTestCase
{
    public function testScopeVariableName(): void
    {
        $templateGenerator = new class () {
            use VariableNameScoperTrait {
                scopeVariableName as private doScopeVariableName;
            }

            public function scopeVariableName(string $prefix, array &$context): string
            {
                return $this->doScopeVariableName($prefix, $context);
            }
        };

        $context = [];

        $this->assertSame('$foo_0', $templateGenerator->scopeVariableName('foo', $context));
        $this->assertSame('$foo_1', $templateGenerator->scopeVariableName('foo', $context));
        $this->assertSame(['variable_counters' => ['foo' => 2]], $context);
    }
}
