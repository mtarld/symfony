<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\CacheWarmer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\JsonMarshaller\Exception\ExceptionInterface;
use Symfony\Component\JsonMarshaller\Marshal\Template\Template as MarshalTemplate;
use Symfony\Component\JsonMarshaller\Type\Type;
use Symfony\Component\JsonMarshaller\Unmarshal\Template\Template as UnmarshalTemplate;

/**
 * Generates marshal and unmarshal templates PHP files.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
final class TemplateCacheWarmer extends CacheWarmer
{
    /**
     * @param list<string> $marshallable
     */
    public function __construct(
        private readonly array $marshallable,
        private readonly MarshalTemplate $marshalTemplate,
        private readonly UnmarshalTemplate $unmarshalTemplate,
        private readonly string $templateCacheDir,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function warmUp(string $cacheDir): array
    {
        if (!file_exists($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, recursive: true);
        }

        foreach ($this->marshallable as $m) {
            $this->warmTemplates(Type::fromString($m));
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
            $this->writeCacheFile($this->marshalTemplate->path($type), $this->marshalTemplate->content($type));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" marshal template for "{type}": {exception}', [
                'type' => (string) $type,
                'exception' => $e,
            ]);
        }

        try {
            $this->writeCacheFile($this->unmarshalTemplate->path($type, false), $this->unmarshalTemplate->content($type, false, []));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" unmarshal eager template for "{type}": {exception}', [
                'type' => (string) $type,
                'exception' => $e,
            ]);
        }

        try {
            $this->writeCacheFile($this->unmarshalTemplate->path($type, true), $this->unmarshalTemplate->content($type, true, []));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate "json" unmarshal lazy template for "{type}": {exception}', [
                'type' => (string) $type,
                'exception' => $e,
            ]);
        }
    }
}
