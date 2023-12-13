<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\CacheWarmer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\JsonEncoder\Exception\ExceptionInterface;
use Symfony\Component\JsonEncoder\Template\Decode\Template as DecodeTemplate;
use Symfony\Component\JsonEncoder\Template\Encode\Template as EncodeTemplate;
use Symfony\Component\TypeInfo\Type;

/**
 * Generates encode and decode templates PHP files.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TemplateCacheWarmer extends CacheWarmer
{
    /**
     * @param list<class-string> $encodableClassNames
     */
    public function __construct(
        private readonly array $encodableClassNames,
        private readonly EncodeTemplate $encodeTemplate,
        private readonly DecodeTemplate $decodeTemplate,
        private readonly string $templateCacheDir,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function warmUp(string $cacheDir, string $buildDir = null): array
    {
        if (!file_exists($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, recursive: true);
        }

        foreach ($this->encodableClassNames as $className) {
            $this->warmTemplates(Type::object($className));
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function warmTemplates(Type $type): void
    {
        try {
            $this->writeCacheFile($this->encodeTemplate->getPath($type, EncodeTemplate::ENCODE_TO_STRING), $this->encodeTemplate->generateContent($type));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" encode template for "{type}": {exception}', ['type' => (string) $type, 'exception' => $e]);
        }

        try {
            $this->writeCacheFile($this->encodeTemplate->getPath($type, EncodeTemplate::ENCODE_TO_STREAM), $this->encodeTemplate->generateStreamContent($type));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" encode stream template for "{type}": {exception}', ['type' => (string) $type, 'exception' => $e]);
        }

        try {
            $this->writeCacheFile($this->encodeTemplate->getPath($type, EncodeTemplate::ENCODE_TO_RESOURCE), $this->encodeTemplate->generateResourceContent($type));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" encode resource template for "{type}": {exception}', ['type' => (string) $type, 'exception' => $e]);
        }

        try {
            $this->writeCacheFile($this->decodeTemplate->getPath($type, DecodeTemplate::DECODE_FROM_STRING), $this->decodeTemplate->generateContent($type));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" decode template for "{type}": {exception}', ['type' => (string) $type, 'exception' => $e]);
        }

        try {
            $this->writeCacheFile($this->decodeTemplate->getPath($type, DecodeTemplate::DECODE_FROM_STREAM), $this->decodeTemplate->generateStreamContent($type));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" decode stream template for "{type}": {exception}', ['type' => (string) $type, 'exception' => $e]);
        }

        try {
            $this->writeCacheFile($this->decodeTemplate->getPath($type, DecodeTemplate::DECODE_FROM_RESOURCE), $this->decodeTemplate->generateStreamContent($type));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" decode resource template for "{type}": {exception}', ['type' => (string) $type, 'exception' => $e]);
        }
    }
}
