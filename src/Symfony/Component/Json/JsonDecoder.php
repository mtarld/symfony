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
use Symfony\Component\Encoder\DecoderInterface;
use Symfony\Component\Encoder\Exception\InvalidResourceException;
use Symfony\Component\Encoder\Exception\RuntimeException;
use Symfony\Component\Encoder\Instantiator\InstantiatorInterface;
use Symfony\Component\Encoder\Stream\MemoryStream;
use Symfony\Component\Json\Template\Decode\Template;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @phpstan-import-type DecodeConfig from DecoderInterface
 *
 * @phpstan-type JsonDecodeConfig DecodeConfig&array{
 *   force_generate_template?: bool,
 *   json_decode_flags?: int,
 * }
 */
final readonly class JsonDecoder implements DecoderInterface
{
    public function __construct(
        private Template $template,
        private InstantiatorInterface $instantiator,
        private string $templateCacheDir,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param JsonDecodeConfig $config
     */
    public function decode(string $input, Type $type, array $config = []): mixed
    {
        $inputResource = (new MemoryStream())->getResource();

        if (false === @fwrite($inputResource, $input)) {
            throw new InvalidResourceException($inputResource);
        }

        if (false === @rewind($inputResource)) {
            throw new InvalidResourceException($inputResource);
        }

        $path = $this->template->getPath($type, false);

        if (!file_exists($path) || ($config['force_generate_template'] ?? false)) {
            $content = $this->template->generateContent($type, false, $config);

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

        return (require $path)($inputResource, $config, $this->instantiator, $this->runtimeServices);
    }
}
