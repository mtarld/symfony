<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize;

use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Serialize\Configuration\Configuration;
use Symfony\Component\Serializer\Serialize\Template\TemplateFactory;
use Symfony\Component\Serializer\Stream\MemoryStream;
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Serializer implements SerializerInterface
{
    public function __construct(
        private readonly TemplateFactory $templateFactory,
        private readonly string $templateCacheDir,
    ) {
    }

    public function serialize(mixed $data, string $format, StreamInterface $output = null, Configuration $configuration = null): string|null
    {
        $shouldOutputString = null === $output;
        $output ??= new MemoryStream();

        $configuration ??= new Configuration();

        $type = $configuration->type() ?? Type::createFromString(get_debug_type($data));

        $template = $this->templateFactory->create($type, $format, $configuration);

        if (!file_exists($template->path) || $configuration->forceGenerateTemplate()) {
            if (!file_exists($this->templateCacheDir)) {
                mkdir($this->templateCacheDir, recursive: true);
            }

            $tmpFile = @tempnam(\dirname($template->path), basename($template->path));
            if (false === @file_put_contents($tmpFile, $template->content())) {
                throw new RuntimeException(sprintf('Failed to write "%s" template file.', $template->path));
            }

            @rename($tmpFile, $template->path);
            @chmod($template->path, 0666 & ~umask());
        }

        (require $template->path)($data, $output->resource(), $configuration);

        return $shouldOutputString ? (string) $output : null;
    }
}
