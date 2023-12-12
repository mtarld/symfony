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
use Symfony\Component\Encoder\Encoded;
use Symfony\Component\Encoder\EncoderInterface;
use Symfony\Component\Encoder\Exception\LogicException;
use Symfony\Component\Encoder\Exception\RuntimeException;
use Symfony\Component\Json\Template\Encode\Template;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final readonly class JsonEncoder implements EncoderInterface
{
    public function __construct(
        private Template $template,
        private string $templateCacheDir,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param array{
     *   type?: Type,
     *   stream?: StreamWriterInterface,
     *   max_depth?: int,
     *   date_time_format?: string,
     *   force_generate_template?: bool,
     *   json_encode_flags?: int,
     * } $config
     */
    public function encode(mixed $data, array $config = []): \Traversable&\Stringable
    {
        if (null === ($type = $config['type'] ?? null)) {
            $type = \is_object($data) ? Type::object($data::class) : new Type(get_debug_type($data));
        }

        $stream = $config['stream'] ?? null;
        $isResourceStream = null !== $stream && method_exists($stream, 'getResource');

        $encodeTo = match (true) {
            $isResourceStream => Template::ENCODE_TO_RESOURCE,
            null !== $stream => Template::ENCODE_TO_STREAM,
            default => Template::ENCODE_TO_STRING,
        };

        $path = $this->template->getPath($type, $encodeTo);

        if (!file_exists($path) || ($config['force_generate_template'] ?? false)) {
            $content = match ($encodeTo) {
                Template::ENCODE_TO_RESOURCE => $this->template->generateResourceContent($type, $config),
                Template::ENCODE_TO_STREAM => $this->template->generateStreamContent($type, $config),
                Template::ENCODE_TO_STRING => $this->template->generateContent($type, $config),
                default => throw new LogicException(sprintf('Encoding to "%s" is not handled.', $encodeTo)),
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

        if (null !== $stream) {
            (require $path)($data, $isResourceStream ? $stream->getResource() : $stream, $config, $this->runtimeServices);

            return new Encoded(new \EmptyIterator());
        }

        return new Encoded((require $path)($data, $config, $this->runtimeServices));
    }
}
