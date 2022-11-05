<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller;

use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Output\OutputInterface;

final class Marshaller implements MarshallerInterface
{
    public function __construct(
        // private readonly TemplateLoader $templateLoader,
    ) {
    }

    public function marshal(object $object, string $format, OutputInterface $output, Context $context = null): void
    {
        $context = $context ?? new Context();

        marshal($object, $output->stream(), $format);
    }
}
