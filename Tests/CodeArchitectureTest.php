<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests;

use phpDocumentor\Reflection\Types\ContextFactory;
use PHPUnit\Framework\TestCase;

final class CodeArchitectureTest extends TestCase
{
    public function testNeverRelyOnInternal(): void
    {
        $includedFiles = [];
        $path = realpath(__DIR__.'/..');
        $contextFactory = new ContextFactory();

        $phpFiles = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+\.php$/i',
            \RecursiveRegexIterator::GET_MATCH
        );

        foreach ($phpFiles as $file) {
            $sourceFile = realpath($file[0]);
            $subPath = str_replace($path.'/', '', $sourceFile);

            if (preg_match('#^(vendor|Tests|Resources|Internal)#', $subPath)) {
                continue;
            }

            try {
                require_once $sourceFile;
            } catch (\Throwable) {
                continue;
            }

            $includedFiles[$sourceFile] = true;
        }

        foreach (get_declared_classes() as $class) {
            $reflection = new \ReflectionClass($class);
            if (!isset($includedFiles[$reflection->getFileName()])) {
                continue;
            }

            $context = $contextFactory->createFromReflector($reflection);
            foreach ($context->getNamespaceAliases() as $use) {
                if (str_starts_with($use, 'Symfony\\Component\\Marshaller\\Internal')) {
                    $this->fail(sprintf('Class "%s" is relying on an internal class: "%s".', $class, $use));
                }
            }

            $this->addToAssertionCount(1);
        }
    }
}
