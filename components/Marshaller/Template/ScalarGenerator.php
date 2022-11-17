<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template;

use Symfony\Component\Marshaller\Type\Type;

/**
 * @internal
 */
abstract class ScalarGenerator
{
    use PhpWriterTrait;

    abstract protected function scalar(Type $type, string $accessor, array $context): string;

    final public function generate(Type $type, string $accessor, array $context): string
    {
        $template = '';

        if ($context['validate_data']) {
            $template .= $this->writeLine(sprintf('if (!(%s)) {', $type->validator($accessor)), $context);
            ++$context['indentation_level'];

            $template .= $this->writeLine(sprintf("throw new \UnexpectedValueException('Invalid \"%s\" type');", $context['readable_accessor']), $context);
            --$context['indentation_level'];

            $template .= $this->writeLine('}', $context);
        }

        $template .= $this->scalar($type, $accessor, $context);

        return $template;
    }
}
