<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\CacheWarmer;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\SerDes\Context\ContextBuilder\SerializeContextBuilderInterface;
use Symfony\Component\SerDes\Exception\ExceptionInterface;
use Symfony\Component\SerDes\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\VarExporter\ProxyHelper;

use function Symfony\Component\SerDes\serialize_generate;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class SerDesCacheWarmer extends CacheWarmer
{
    /**
     * @param iterable<SerializeContextBuilderInterface> $contextBuilders
     * @param list<string>                               $formats
     */
    public function __construct(
        private readonly SerializableResolverInterface $serializableResolver,
        private readonly iterable $contextBuilders,
        private readonly string $templateCacheDir,
        private readonly string $lazyObjectCacheDir,
        private readonly array $formats,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function warmUp(string $cacheDir): array
    {
        if (!file_exists($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, recursive: true);
        }

        if (!file_exists($this->lazyObjectCacheDir)) {
            mkdir($this->lazyObjectCacheDir, recursive: true);
        }

        foreach ($this->serializableResolver->resolve() as $className) {
            foreach ($this->formats as $format) {
                $this->warmClassTemplate($className, $format);
            }

            $this->warmClassLazyObject($className);
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * @param class-string $className
     */
    private function warmClassTemplate(string $className, string $format): void
    {
        try {
            $context = [
                'cache_dir' => $this->templateCacheDir,
                'template_exists' => false,
            ];

            foreach ($this->contextBuilders as $contextBuilder) {
                $context = $contextBuilder->build($context, true);
            }

            $path = sprintf('%s%s%s.%s.php', $this->templateCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className), $format);

            $this->writeCacheFile($path, serialize_generate($className, $format, $context));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate template for "{className}": {exception}', ['className' => $className, 'exception' => $e]);
        }
    }

    /**
     * @param class-string $className
     */
    private function warmClassLazyObject(string $className): void
    {
        $path = sprintf('%s%s%s.php', $this->lazyObjectCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className));

        $this->writeCacheFile($path, sprintf(
            'class %s%s',
            sprintf('%sGhost', preg_replace('/\\\\/', '', $className)),
            ProxyHelper::generateLazyGhost(new \ReflectionClass($className)),
        ));
    }
}
