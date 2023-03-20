<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json;

use Psr\Container\ContainerInterface;
use Symfony\Component\Encoder\Exception\RuntimeException;
use Symfony\Component\Encoder\Stream\MemoryStream;
use Symfony\Component\Encoder\Stream\StreamInterface;
use Symfony\Component\Encoder\StreamingEncoderInterface;
use Symfony\Component\Json\Template\Encode\Template;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @phpstan-import-type JsonEncodeConfig from JsonEncoder
 */
final readonly class JsonStreamingEncoder implements StreamingEncoderInterface
{
    public function __construct(
        private Template $template,
        private string $templateCacheDir,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param EncodeConfig $config
     */
    public function encode(mixed $data, array $config = []): StreamInterface
    {
        if (null === ($type = $config['type'] ?? null)) {
            $type = \is_object($data) ? Type::object($data::class) : new Type(get_debug_type($data));
        }

        $path = $this->template->getPath($type, true);

        if (!file_exists($path) || ($config['force_generate_template'] ?? false)) {
            $content = $this->template->generateContent($type, true, $config);

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

        $output = new MemoryStream();
        (require $path)($data, $output->getResource(), $config, $this->runtimeServices);

        return $output;
    }
}
