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
use Symfony\Component\SerDes\Attribute\Nullable;
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
final class SerDesCacheWarmer implements CacheWarmerInterface
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
        return true;
    }

    /**
     * @param class-string $className
     */
    private function warmClassTemplate(string $className, string $format): void
    {
        $nullable = $this->nullableData;

        foreach ((new \ReflectionClass($className))->getAttributes() as $attribute) {
            if (Nullable::class !== $attribute->getName()) {
                continue;
            }

            /** @var Nullable $attributeInstance */
            $attributeInstance = $attribute->newInstance();

            $nullable = $attributeInstance->nullable;

            break;
        }

        if ($nullable) {
            $className = '?'.$className;
        }

        if (file_exists($path = sprintf('%s%s%s.%s.php', $this->templateCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className), $format))) {
            return;
        }

        try {
            $context = [
                'cache_dir' => $this->templateCacheDir,
                'template_exists' => false,
            ];

            foreach ($this->contextBuilders as $contextBuilder) {
                $context = $contextBuilder->build($context, true);
            }

            file_put_contents($path, serialize_generate($className, $format, $context));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate template for "{className}": {exception}', ['className' => $className, 'exception' => $e]);
        }
    }

    /**
     * @param class-string $className
     */
    private function warmClassLazyObject(string $className): void
    {
        if (file_exists($path = sprintf('%s%s%s.php', $this->lazyObjectCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className)))) {
            return;
        }

        file_put_contents($path, sprintf(
            'class %s%s',
            sprintf('%sGhost', preg_replace('/\\\\/', '', $className)),
            ProxyHelper::generateLazyGhost(new \ReflectionClass($className)),
        ));
    }

    /**
     * @param class-string $className
     */
    private function isTemplateAcceptingNull(string $className): bool
    {
        foreach ((new \ReflectionClass($className))->getAttributes() as $attribute) {
            if (Nullable::class !== $attribute->getName()) {
                continue;
            }

            /** @var Nullable $attributeInstance */
            $attributeInstance = $attribute->newInstance();

            return $attributeInstance->nullable;
        }

        return $this->nullableData;
    }
}
