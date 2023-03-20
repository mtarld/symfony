<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\CacheWarmer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Encoder\Exception\ExceptionInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\Json\Template\Decode\Template as DecodeTemplate;
use Symfony\Component\Json\Template\Encode\Template as EncodeTemplate;
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

    public function warmUp(string $cacheDir): array
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
            $this->writeCacheFile($this->encodeTemplate->getPath($type, false), $this->encodeTemplate->generateContent($type, false));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" encode eager template for "{type}": {exception}', [
                'type' => (string) $type,
                'exception' => $e,
            ]);
        }

        try {
            $this->writeCacheFile($this->encodeTemplate->getPath($type, true), $this->encodeTemplate->generateContent($type, true));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" encode lazy template for "{type}": {exception}', [
                'type' => (string) $type,
                'exception' => $e,
            ]);
        }

        try {
            $this->writeCacheFile($this->decodeTemplate->getPath($type, false), $this->decodeTemplate->generateContent($type, false, []));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" decode eager template for "{type}": {exception}', [
                'type' => (string) $type,
                'exception' => $e,
            ]);
        }

        try {
            $this->writeCacheFile($this->decodeTemplate->getPath($type, true), $this->decodeTemplate->generateContent($type, true, []));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" decode lazy template for "{type}": {exception}', [
                'type' => (string) $type,
                'exception' => $e,
            ]);
        }
    }
}
