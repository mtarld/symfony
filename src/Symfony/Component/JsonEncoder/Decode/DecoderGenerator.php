<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Decode;

use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelBuilder;
use Symfony\Component\JsonEncoder\Exception\RuntimeException;
use Symfony\Component\TypeInfo\Type;

/**
 * Generates and writes decoders PHP files.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final readonly class DecoderGenerator
{
    private PhpAstBuilder $phpAstBuilder;
    private PrettyPrinterAbstract $phpPrinter;

    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $cacheDir,
    ) {
        $this->phpAstBuilder = new PhpAstBuilder();
        $this->phpPrinter = new Standard();
    }

    /**
     * Generates and writes a decoder PHP file and return its path.
     *
     * @param array<string, mixed> $config
     */
    public function generate(Type $type, DecodeFrom $decodeFrom, array $config = []): string
    {
        $path = $this->getPath($type, $decodeFrom);
        if (file_exists($path) && !($config['force_generation'] ?? false)) {
            return $path;
        }

        $dataModel = $this->dataModelBuilder->build($type, $config);
        $nodes = $this->phpAstBuilder->build($dataModel, $decodeFrom, $config);
        $content = $this->phpPrinter->prettyPrintFile($nodes)."\n";

        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, recursive: true);
        }

        $tmpFile = @tempnam(\dirname($path), basename($path));
        if (false === @file_put_contents($tmpFile, $content)) {
            throw new RuntimeException(sprintf('Failed to write "%s" decoder file.', $path));
        }

        @rename($tmpFile, $path);
        @chmod($path, 0666 & ~umask());

        return $path;
    }

    private function getPath(Type $type, DecodeFrom $decodeFrom): string
    {
        return sprintf('%s%s%s.json.%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) $type), $decodeFrom->value);
    }
}
