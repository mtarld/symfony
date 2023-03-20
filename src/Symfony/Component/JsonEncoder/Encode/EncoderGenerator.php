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

use PhpParser\PhpVersion;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder;
use Symfony\Component\JsonEncoder\DataModel\VariableDataAccessor;
use Symfony\Component\JsonEncoder\Exception\RuntimeException;
use Symfony\Component\JsonEncoder\PhpPrinter;
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
    private PhpPrinter $phpPrinter;

    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        private string $encodersDir,
    ) {
        $this->phpAstBuilder = new PhpAstBuilder();
        $this->phpOptimizer = new PhpOptimizer();
        $this->phpPrinter = class_exists(PhpVersion::class) ? new PhpPrinter(['phpVersion' => PhpVersion::fromComponents(8, 1)]) : new PhpPrinter();
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

        if (!file_exists($this->encodersDir)) {
            mkdir($this->encodersDir, recursive: true);
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
        return sprintf('%s%s%s.json.%s.php', $this->encodersDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) $type), $encodeAs->value);
    }
}
