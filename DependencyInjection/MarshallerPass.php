<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MarshallerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('marshaller')) {
            return;
        }

        // TODO fixme

        $marshallerDefinition = $container->getDefinition('marshaller');
        $marshallerDefinition->replaceArgument(1, $this->findAndSortTaggedServices('marshaller.context.context_builder.marshal_generate', $container));
    }
}
