<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller;

use Psr\Container\ContainerInterface;
use Symfony\Component\JsonMarshaller\Exception\InvalidResourceException;
use Symfony\Component\JsonMarshaller\Exception\RuntimeException;
use Symfony\Component\JsonMarshaller\Marshal\Template\Template;
use Symfony\Component\JsonMarshaller\Type\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 */
final class JsonMarshaller implements MarshallerInterface
{
    public function __construct(
        private readonly Template $template,
        private readonly string $templateCacheDir,
        private readonly ?ContainerInterface $runtimeServices = null,
    ) {
    }

    public function marshal(mixed $data, array $config = [], mixed $output = null): string|null
    {
        $shouldOutputString = false;

        if (null === $output) {
            if (false === $output = @fopen('php://memory', 'w+')) {
                throw new RuntimeException('Cannot open "php://memory" resource');
            }

            $shouldOutputString = true;
        }

        $type = $config['type'] ?? Type::fromString(get_debug_type($data));
        $config['json_encode_flags'] ??= 0;

        $path = $this->template->path($type);

        if (!file_exists($path) || ($config['force_generate_template'] ?? false)) {
            $content = $this->template->content($type, $config);

            if (!file_exists($this->templateCacheDir)) {
                mkdir($this->templateCacheDir, recursive: true);
            }

            $tmpFile = @tempnam(\dirname($path), basename($path));
            if (false === @file_put_contents($tmpFile, $content)) {
                throw new RuntimeException(sprintf('Failed to write "%s" template file.', $path));
            }

            @rename($tmpFile, $path);
            @chmod($path, 0666 & ~umask());
        }

        (require $path)($data, $output, $config, $this->runtimeServices);

        if (!$shouldOutputString) {
            return null;
        }

        if (false === @rewind($output)) {
            throw new InvalidResourceException($output);
        }

        if (false === $content = @stream_get_contents($output)) {
            throw new InvalidResourceException($output);
        }

        return $content;
    }
}
