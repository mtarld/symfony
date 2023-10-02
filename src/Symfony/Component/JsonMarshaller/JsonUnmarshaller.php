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
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\JsonMarshaller\Unmarshal\Instantiator\InstantiatorInterface;
use Symfony\Component\JsonMarshaller\Unmarshal\Template\Template;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.1
 *
 * @phpstan-import-type UnmarshalConfig from UnmarshallerInterface
 *
 * @phpstan-type JsonUnmarshalConfig UnmarshalConfig&array{
 *   force_generate_template?: bool,
 *   json_decode_flags?: int,
 * }
 */
final readonly class JsonUnmarshaller implements UnmarshallerInterface
{
    public function __construct(
        private Template $template,
        private InstantiatorInterface $instantiator,
        private string $templateCacheDir,
        private ?ContainerInterface $runtimeServices = null,
        private bool $lazy = false,
    ) {
    }

    /**
     * @param JsonUnmarshalConfig $config
     */
    public function unmarshal(mixed $input, Type $type, array $config = []): mixed
    {
        if (\is_string($input)) {
            $inputString = $input;

            if (false === $input = @fopen('php://memory', 'w+')) {
                throw new RuntimeException('Cannot open "php://memory" resource');
            }

            if (false === @fwrite($input, $inputString)) {
                throw new InvalidResourceException($input);
            }

            if (false === @rewind($input)) {
                throw new InvalidResourceException($input);
            }
        }

        $path = $this->template->path($type, $this->lazy);

        if (!file_exists($path) || ($config['force_generate_template'] ?? false)) {
            $content = $this->template->content($type, $this->lazy, $config);

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

        return (require $path)($input, $config, $this->instantiator, $this->runtimeServices);
    }
}
