<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Template;

use PhpParser\PrettyPrinterAbstract;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Metadata\ClassMetadataFactory;
use Symfony\Component\Marshaller\Metadata\ValueMetadata;
use Symfony\Component\Marshaller\Template\Generator\Generator;

final class TemplateLoader
{
    public function __construct(
        private readonly Generator $templateGenerator,
        private readonly PrettyPrinterAbstract $phpPrinter,
        private readonly TemplateFilenameBuilder $templateFilenameBuilder,
        private readonly ClassMetadataFactory $classMetadataFactory,
        private readonly Filesystem $filesystem,
        private readonly string $cacheDir,
    ) {
    }

    public function load(\ReflectionClass $class, Context $context): callable
    {
        $path = $this->templatePath($class, $context);

        if (!$this->filesystem->exists($path)) {
            $this->save($class, $context);
        }

        return require $path;
    }

    public function save(\ReflectionClass $class, Context $context): void
    {
        $statements = $this->templateGenerator->generate(
            new ValueMetadata('object', class: $this->classMetadataFactory->forClass($class, $context)),
            $context,
        );

        // dd($this->phpPrinter->prettyPrintFile($statements));

        $this->filesystem->dumpFile(
            $this->templatePath($class, $context),
            $this->phpPrinter->prettyPrintFile($statements),
        );
    }

    private function templatePath(\ReflectionClass $class, Context $context): string
    {
        $filename = $this->templateFilenameBuilder->build($class, $context);

        return sprintf('%s/marshaller/%s', $this->cacheDir, $filename);
    }
}
