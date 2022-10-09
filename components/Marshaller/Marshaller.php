<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Output\OutputInterface;
use Symfony\Component\Marshaller\Template\TemplateLoader;

final class Marshaller implements MarshallerInterface
{
    public function __construct(
        private readonly TemplateLoader $templateLoader,
    ) {
    }

    public function marshal(object $object, OutputInterface $output, Context $context = null): void
    {
        $context = $context ?? new Context();

        $marshal = $this->templateLoader->load(new \ReflectionClass($object), $context);
        $marshal($object, $context, $output);
    }
}
