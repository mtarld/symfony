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
use Symfony\Component\Encoder\Exception\LogicException;
use Symfony\Component\Encoder\Exception\RuntimeException;
use Symfony\Component\Encoder\Instantiator\InstantiatorInterface;
use Symfony\Component\Encoder\Instantiator\LazyInstantiatorInterface;
use Symfony\Component\Encoder\Stream\BufferedStream;
use Symfony\Component\Encoder\Stream\SeekableStreamInterface;
use Symfony\Component\Encoder\Stream\StreamReaderInterface;
use Symfony\Component\Json\Template\Decode\Template;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final readonly class JsonDecoder implements DecoderInterface
{
    public function __construct(
        private Template $template,
        private InstantiatorInterface $instantiator,
        private LazyInstantiatorInterface $lazyInstantiator,
        private string $templateCacheDir,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param array{
     *   date_time_format?: string,
     *   force_generate_template?: bool,
     *   json_decode_flags?: int,
     * } $config
     */
    public function decode((StreamReaderInterface&SeekableStreamInterface)|\Traversable|\Stringable|string $input, Type $type, array $config = []): mixed
    {
        if ($input instanceof \Traversable && !$input instanceof StreamReaderInterface) {
            $chunks = $input;
            $input = new BufferedStream();
            foreach ($chunks as $chunk) {
                $input->write($chunk);
            }
        }

        $isStream = $input instanceof StreamReaderInterface;
        $isResourceStream = $isStream && method_exists($input, 'getResource');

        $decodeFrom = match (true) {
            $isResourceStream => Template::DECODE_FROM_RESOURCE,
            $isStream => Template::DECODE_FROM_STREAM,
            default => Template::DECODE_FROM_STRING,
        };

        $path = $this->template->getPath($type, $decodeFrom);

        if (!file_exists($path) || ($config['force_generate_template'] ?? false)) {
            $content = match ($decodeFrom) {
                Template::DECODE_FROM_RESOURCE => $this->template->generateResourceContent($type, $config),
                Template::DECODE_FROM_STREAM => $this->template->generateStreamContent($type, $config),
                Template::DECODE_FROM_STRING => $this->template->generateContent($type, $config),
                default => throw new LogicException(sprintf('Decoding from "%s" is not handled.', $decodeFrom)),
            };

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

        return (require $path)($isResourceStream ? $input->getResource() : $input, $config, $isStream ? $this->lazyInstantiator : $this->instantiator, $this->runtimeServices);
    }
}
