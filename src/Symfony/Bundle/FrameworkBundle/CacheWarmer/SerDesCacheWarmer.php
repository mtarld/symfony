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
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\SerDes\Attribute\Serializable;
use Symfony\Component\SerDes\Context\ContextBuilderInterface;
use Symfony\Component\SerDes\Exception\ExceptionInterface;
use Symfony\Component\SerDes\SerializableResolverInterface;
use Symfony\Component\VarExporter\ProxyHelper;

use function Symfony\Component\SerDes\serialize_generate;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.3
 */
final class SerDesCacheWarmer implements CacheWarmerInterface
{
    /**
     * @param iterable<ContextBuilderInterface> $contextBuilders
     * @param list<string>                      $formats
     */
    public function __construct(
        private readonly SerializableResolverInterface $serializableResolver,
        private readonly iterable $contextBuilders,
        private readonly string $templateCacheDir,
        private readonly string $lazyObjectCacheDir,
        private readonly array $formats,
        private readonly bool $nullableData,
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

        foreach ($this->serializableResolver->resolve() as $class => $attribute) {
            foreach ($this->formats as $format) {
                $this->warmClassTemplate($class, $attribute, $format);
            }

            $this->warmClassLazyObject($class);
        }

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }

    /**
     * @param class-string $class
     */
    private function warmClassTemplate(string $class, Serializable $attribute, string $format): void
    {
        if ($attribute->nullable ?? $this->nullableData) {
            $class = '?'.$class;
        }

        if (file_exists($path = sprintf('%s%s%s.%s.php', $this->templateCacheDir, \DIRECTORY_SEPARATOR, md5($class), $format))) {
            return;
        }

        try {
            $context = ['cache_dir' => $this->templateCacheDir];

            foreach ($this->contextBuilders as $contextBuilder) {
                $context = $contextBuilder->buildSerializeContext($context, true);
            }

            file_put_contents($path, serialize_generate($class, $format, $context));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate template for "{class}": {exception}', ['class' => $class, 'exception' => $e]);
        }
    }

    /**
     * @param class-string $class
     */
    private function warmClassLazyObject(string $class): void
    {
        if (file_exists($path = sprintf('%s%s%s.php', $this->lazyObjectCacheDir, \DIRECTORY_SEPARATOR, md5($class)))) {
            return;
        }

        file_put_contents($path, sprintf(
            'class %s%s',
            sprintf('%sGhost', preg_replace('/\\\\/', '', $class)),
            ProxyHelper::generateLazyGhost(new \ReflectionClass($class)),
        ));
    }
}
