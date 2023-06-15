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
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\Serializer\Serialize\Template\TemplateHelper;
use Symfony\Component\Serializer\Serialize\Template\TemplateVariation;
use Symfony\Component\Serializer\Type\TypeFactory;
use Symfony\Component\VarExporter\ProxyHelper;

use function Symfony\Component\Serializer\serialize_generate;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class SerializerDeserializerCacheWarmer extends CacheWarmer
{
    private readonly TemplateHelper $templateHelper;

    /**
     * @param list<string> $formats
     */
    public function __construct(
        private readonly SerializableResolverInterface $serializableResolver,
        private readonly string $templateCacheDir,
        private readonly string $lazyObjectCacheDir,
        private readonly array $formats,
        private readonly int $maxVariants,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->templateHelper = new TemplateHelper();
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
            $variants = $this->templateHelper->classTemplateVariants($className);

            if (\count($variants) > $this->maxVariants) {
                $this->logger->debug('Too many variants for "{className}", keeping only the first {maxVariants}.', ['className' => $className, 'maxVariants' => $this->maxVariants]);
                $variants = \array_slice($variants, offset: 0, length: $this->maxVariants);
            }

            foreach ($this->formats as $format) {
                $this->warmClassTemplate($className, $variants, $format);
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
     * @param class-string                  $className
     * @param list<list<TemplateVariation>> $variants
     */
    private function warmClassTemplate(string $className, array $variants, string $format): void
    {
        try {
            foreach ($variants as $variant) {
                $variantContext = [];

                $groupVariations = array_filter($variant, fn (TemplateVariation $v): bool => 'group' === $v->type);
                if ([] !== $groupVariations) {
                    $variantContext['groups'] = array_map(fn (TemplateVariation $v): string => $v->value, $groupVariations);
                }

                $variantFilename = $this->templateHelper->templateFilename($className, $format, $variantContext);

                $this->writeCacheFile(
                    $this->templateCacheDir.\DIRECTORY_SEPARATOR.$variantFilename,
                    serialize_generate(TypeFactory::createFromString($className), $format, $variantContext),
                );
            }
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
