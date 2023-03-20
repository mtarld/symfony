<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\ContextBuilderInterface;
use Symfony\Component\Marshaller\Context\ContextInterface;
use Symfony\Component\Marshaller\Stream\StreamInterface;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.4
 */
final class Marshaller implements MarshallerInterface
{
    /**
     * @param iterable<ContextBuilderInterface> $contextBuilders
     */
    public function __construct(
        private readonly iterable $contextBuilders,
        private readonly string $templateCacheDir,
    ) {
    }

    public function marshal(mixed $data, string $format, mixed $output, ContextInterface|array $context = []): void
    {
        if ($context instanceof ContextInterface) {
            $context = $context->toArray();
        }

        if ($output instanceof StreamInterface) {
            $output = $output->resource();
        }

        $context['type'] = $context['type'] ?? get_debug_type($data);
        $context['cache_dir'] = $context['cache_dir'] ?? $this->templateCacheDir;

        $templateExists = file_exists(sprintf('%s%s%s.%s.php', $context['cache_dir'], \DIRECTORY_SEPARATOR, md5($context['type']), $format));

        foreach ($this->contextBuilders as $contextBuilder) {
            $context = $contextBuilder->buildMarshalContext($context, !$templateExists);
        }

        marshal($data, $output, $format, $context);
    }

    public function unmarshal(mixed $input, string $type, string $format, ContextInterface|array $context = []): mixed
    {
        if ($context instanceof ContextInterface) {
            $context = $context->toArray();
        }

        if ($input instanceof StreamInterface) {
            $input = $input->resource();
        }

        foreach ($this->contextBuilders as $contextBuilder) {
            $context = $contextBuilder->buildUnmarshalContext($context);
        }

        return unmarshal($input, $type, $format, $context);
    }
}
