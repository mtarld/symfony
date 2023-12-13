<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Encode;

use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\JsonEncoder\DataModel\VariableDataAccessor;
use Symfony\Component\JsonEncoder\Exception\RuntimeException;
use Symfony\Component\TypeInfo\Type;

/**
 * Generates and write encoders PHP files.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class EncoderGenerator
{
    private PhpAstBuilder $phpAstBuilder;
    private PhpOptimizer $phpOptimizer;
    private PrettyPrinterAbstract $phpPrinter;

    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $cacheDir,
    ) {
        $this->phpAstBuilder = new PhpAstBuilder();
        $this->phpOptimizer = new PhpOptimizer();
        $this->phpPrinter = new Standard();
    }

    /**
     * Generates and writes an encoder PHP file and return its path.
     *
     * @param array<string, mixed> $config
     */
    public function generate(Type $type, EncodeAs $encodeAs, array $config = []): string
    {
        $path = $this->getPath($type, $encodeAs);
        if (file_exists($path) && !($config['force_generation'] ?? false)) {
            return $path;
        }

        $dataModel = $this->dataModelBuilder->build($type, new VariableDataAccessor('data'), $config);

        $nodes = $this->phpAstBuilder->build($dataModel, $encodeAs, $config);
        $nodes = $this->phpOptimizer->optimize($nodes);

        $content = $this->phpPrinter->prettyPrintFile($nodes)."\n";

        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        $tmpFile = @tempnam(\dirname($path), basename($path));
        if (false === @file_put_contents($tmpFile, $content)) {
            throw new RuntimeException(sprintf('Failed to write "%s" encoder file.', $path));
        }

        @rename($tmpFile, $path);
        @chmod($path, 0666 & ~umask());

        return $path;
    }

    private function getPath(Type $type, EncodeAs $encodeAs): string
    {
        return sprintf('%s%s%s.json.%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) $type), $encodeAs->value);
    }
}
