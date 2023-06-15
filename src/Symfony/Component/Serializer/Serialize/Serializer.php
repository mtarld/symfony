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
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\TypeFactory;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class Serializer implements SerializerInterface
{
    public function __construct(
        private readonly Template $template,
        private readonly string $templateCacheDir,
    ) {
    }

    public function serialize(mixed $data, string $format, mixed $output, ContextInterface|array $context = []): void
    {
        if ($output instanceof StreamInterface) {
            $output = $output->resource();
        }

        if ($context instanceof ContextInterface) {
            $context = $context->toArray();
        }

        if (\is_string($type = $context['type'] ?? get_debug_type($data))) {
            $type = TypeFactory::createFromString($type);
        }

        $templatePath = $this->template->path($type, $format, $context);

        if (!file_exists($templatePath) || ($context['force_generate_template'] ?? false)) {
            if (!file_exists($this->templateCacheDir)) {
                mkdir($this->templateCacheDir, recursive: true);
            }

            $tmpFile = @tempnam(\dirname($templatePath), basename($templatePath));
            if (false === @file_put_contents($tmpFile, $this->template->content($type, $format, $context))) {
                throw new RuntimeException(sprintf('Failed to write "%s" template file.', $templatePath));
            }

            @rename($tmpFile, $templatePath);
            @chmod($templatePath, 0666 & ~umask());
        }

        (require $templatePath)($data, $output, $context);
    }
}
