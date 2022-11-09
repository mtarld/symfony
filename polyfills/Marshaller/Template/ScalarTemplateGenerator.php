<?php

declare(strict_types=1);

namespace Symfony\Polyfill\Marshaller\Template;

use Symfony\Polyfill\Marshaller\Metadata\HookExtractor;
use Symfony\Polyfill\Marshaller\Metadata\Type;

/**
 * @internal
 */
abstract class ScalarTemplateGenerator
{
    use PhpWriterTrait;

    private readonly HookExtractor $hookExtractor;

    public function __construct()
    {
        $this->hookExtractor = new HookExtractor();
    }

    /**
     * @param array<string, mixed> $context
     */
    abstract protected function generateValue(Type $type, string $accessor, array $context): string;

    /**
     * @param array<string, mixed> $context
     */
    final public function generate(Type $type, string $accessor, array $context): string
    {
        if (null !== $hook = $this->hookExtractor->extractFromType($type, $context)) {
            $hookContext = $context + [
                'fwrite' => $this->fwrite(...),
                'writeLine' => $this->writeLine(...),
            ];

            return $hook($type, $accessor, $hookContext);
        }

        return $this->generateValue($type, $accessor, $context);
    }
}
