<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Cache;

use Symfony\Component\Marshaller\Attribute\Warmable;

final class WarmableResolver
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        private readonly array $paths,
    ) {
    }

    /**
     * @return \Generator<array{0: class-string, 1: Warmable}>
     */
    public function resolve(): \Generator
    {
        foreach ($this->fromPaths($this->paths) as $class) {
            $attribute = null;
            foreach ($class->getAttributes() as $attribute) {
                if (Warmable::class === $attribute->getName()) {
                    $attribute = $attribute->newInstance();
                }
            }

            if (null === $attribute) {
                continue;
            }

            yield [$class->getName(), $attribute];
        }
    }

    /**
     * @return Generator<ReflectionClass>
     */
    private function fromPaths(array $paths): \Generator
    {
        $includedFiles = [];

        foreach ($paths as $path) {
            $phpFiles = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                '/^.+\.php$/i',
                \RecursiveRegexIterator::GET_MATCH
            );

            foreach ($phpFiles as $file) {
                $sourceFile = $file[0];
                if (!preg_match('(^phar:)i', (string) $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                try {
                    require_once $sourceFile;
                } catch (\Throwable) {
                    continue;
                }

                $includedFiles[$sourceFile] = true;
            }
        }

        foreach (get_declared_classes() as $class) {
            $reflectionClass = new \ReflectionClass($class);
            $sourceFile = $reflectionClass->getFileName();

            if (!isset($includedFiles[$sourceFile])) {
                continue;
            }

            yield $reflectionClass;
        }
    }
}
