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

use Symfony\Component\Serializer\ContextInterface;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Serialize\Template\Template;
use Symfony\Component\Serializer\Serialize\Template\TemplateFactory;
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

    public function serialize(mixed $data, string $format, mixed $output, Configuration $configuration = null): void
    {
        if ($output instanceof StreamInterface) {
            $output = $output->resource();
        }

        $type = $configuration->type() ?? Type::createFromString(get_debug_type($data));

        $configuration ??= new Configuration();

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

        (require $template->path)($data, $output, $configuration);
    }
}
