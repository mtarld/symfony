<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Attribute\Marshallable;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class MarshallableResolver implements MarshallableResolverInterface
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        private readonly array $paths,
    ) {
    }

    /**
     * @return iterable<class-string, Marshallable>
     */
    public function resolve(): iterable
    {
        foreach ($this->fromPaths($this->paths) as $class) {
            if ($class->isAbstract() || $class->isInterface() || $class->isTrait()) {
                continue;
            }

            $attributeInstance = null;

            foreach ($class->getAttributes() as $attribute) {
                if (Marshallable::class === $attribute->getName()) {
                    $attributeInstance = $attribute->newInstance();
                }
            }

            /** @var Marshallable|null $attributeInstance */
            if (null === $attributeInstance) {
                continue;
            }

            yield $class->getName() => $attributeInstance;
        }
    }

    /**
     * @param list<string> $paths
     *
     * @return iterable<\ReflectionClass<object>>
     */
    private function fromPaths(array $paths): iterable
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
