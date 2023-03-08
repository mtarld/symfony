<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Cache;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Marshaller\Attribute\Marshallable;
use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\Exception\ExceptionInterface;

use function Symfony\Component\Marshaller\marshal_generate;

use Symfony\Component\Marshaller\MarshallableResolverInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class TemplateCacheWarmer implements CacheWarmerInterface
{
    /**
     * @param iterable<ContextBuilderInterface> $contextBuilders
     * @param list<string>                      $formats
     */
    public function __construct(
        private readonly MarshallableResolverInterface $marshallableResolver,
        private readonly iterable $contextBuilders,
        private readonly string $templateCacheDir,
        private readonly array $formats,
        private readonly bool $nullableData,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function warmUp(string $cacheDir): array
    {
        foreach ($this->marshallableResolver->resolve() as $class => $attribute) {
            foreach ($this->formats as $format) {
                $this->warmClass($class, $attribute, $format);
            }
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * @param class-string $class
     */
    private function warmClass(string $class, Marshallable $attribute, string $format): void
    {
        if ($attribute->nullable ?? $this->nullableData) {
            $class = '?'.$class;
        }

        $path = sprintf('%s%s%s.%s.php', $this->templateCacheDir, \DIRECTORY_SEPARATOR, md5($class), $format);
        if (file_exists($path)) {
            return;
        }

        if (!file_exists($this->templateCacheDir)) {
            mkdir($this->templateCacheDir, recursive: true);
        }

        try {
            file_put_contents($path, $this->generateTemplate($class, $format));
        } catch (ExceptionInterface $e) {
            $this->logger->debug('Cannot generate template for "{class}": {exception}', [
                'class' => $class,
                'exception' => $e,
            ]);
        }
    }

    private function generateTemplate(string $class, string $format): string
    {
        $context = ['cache_dir' => $this->templateCacheDir];

        foreach ($this->contextBuilders as $contextBuilder) {
            $context = $contextBuilder->buildMarshalContext($context, true);
        }

        return marshal_generate($class, $format, $context);
    }
}
