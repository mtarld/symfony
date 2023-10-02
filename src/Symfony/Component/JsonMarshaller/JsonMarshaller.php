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

 *
 * @phpstan-import-type MarshalConfig from MarshallerInterface
 *
 * @phpstan-type JsonMarshalConfig MarshalConfig&array{
 *   force_generate_template?: bool,
 *   json_encode_flags?: int,
 * }
 */
final readonly class JsonMarshaller implements MarshallerInterface
{
    public function __construct(
        private Template $template,
        private string $templateCacheDir,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param JsonMarshalConfig $config
     */
    public function marshal(mixed $data, array $config = [], mixed $output = null): string|null
    {
        $lazy = true;

        if (null === $output) {
            if (false === $output = @fopen('php://memory', 'w+')) {
                throw new RuntimeException('Cannot open "php://memory" resource');
            }

            $lazy = false;
        }

        $type = $config['type'] ?? Type::fromString(get_debug_type($data));
        $path = $this->template->path($type, $lazy);

        if (!file_exists($path) || ($config['force_generate_template'] ?? false)) {
            $content = $this->template->content($type, $lazy, $config);

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

        if ($lazy) {
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
