<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer;

use Symfony\Component\Serializer\Context\ContextBuilder;
use Symfony\Component\Serializer\Serialize\Template\TemplateHelper;
use Symfony\Component\Serializer\Stream\StreamInterface;
use Symfony\Component\Serializer\Type\TypeFactory;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final readonly class Serialize implements SerializeInterface
{
    private TemplateHelper $templateHelper;

    public function __construct(
        // private ContextBuilder $contextBuilder,
        private string $templateCacheDir,
    ) {
        $this->templateHelper = new TemplateHelper();
    }

    public function __invoke(mixed $data, string $format, mixed $output, ContextInterface|array $context = []): void
    {
        if ($output instanceof StreamInterface) {
            $output = $output->resource();
        }

        if ($context instanceof ContextInterface) {
            $context = $context->toArray();
        }

        $context['type'] ??= get_debug_type($data);
        $context['cache_dir'] ??= $this->templateCacheDir;

        if (\is_string($context['type'])) {
            $context['type'] = TypeFactory::createFromString($context['type']);
        }

        $templatePath = $context['cache_dir'].\DIRECTORY_SEPARATOR.$this->templateHelper->templateFilename($context['type'], $format, $context);

        if (!file_exists($templatePath)) {
            $context = $this->contextBuilder->build($context, isSerialization: true);
        }

        serialize($data, $output, $format, $context);
    }
}
