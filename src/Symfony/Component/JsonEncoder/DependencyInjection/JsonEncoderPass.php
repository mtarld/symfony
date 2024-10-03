<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\JsonEncoder\DecoderInterface;
use Symfony\Component\JsonEncoder\EncoderInterface;

/**
 * Injects encodable classes into services and registers aliases.
 *
 * @author Mathias Arlaud<mathias.arlaud@gmail.com>
 */
final class JsonEncoderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('json_encoder.encoder')) {
            return;
        }

        $container->registerAliasForArgument('json_encoder.encoder', EncoderInterface::class, 'json.encoder');
        $container->registerAliasForArgument('json_encoder.decoder', DecoderInterface::class, 'json.decoder');

        $encodablePaths = $container->hasParameter('json_encoder.encodable_paths') ? $container->getParameter('json_encoder.encodable_paths') : [];
        $encodableClassNames = $this->getEncodableClassNames($encodablePaths);

        $container->getDefinition('.json_encoder.cache_warmer.encoder_decoder')
            ->replaceArgument(0, $encodableClassNames);

        $container->getDefinition('.json_encoder.cache_warmer.lazy_ghost')
            ->replaceArgument(0, $encodableClassNames);
    }

    /**
     * @param list<string> $globs
     *
     * @return list<class-string>
     */
    private function getEncodableClassNames(array $globs): iterable
    {
        $encodableClassNames = [];
        $includedFiles = [];

        foreach ($globs as $glob) {
            $paths = glob($glob, (\defined('GLOB_BRACE') ? \GLOB_BRACE : 0) | \GLOB_ONLYDIR | \GLOB_NOSORT);

            foreach ($paths as $path) {
                if (!is_dir($path)) {
                    continue;
                }

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

                if ($reflectionClass->isAbstract() || $reflectionClass->isInterface() || $reflectionClass->isTrait()) {
                    continue;
                }

                $encodableClassNames[] = $reflectionClass->getName();
            }
        }

        return $encodableClassNames;
    }
}
