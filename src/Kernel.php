<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Marshaller\DependencyInjection\MarshallerExtension;
use Symfony\Component\Marshaller\DependencyInjection\MarshallerPass;
use Symfony\Component\Marshaller\NativeContext\MarshalGenerateNativeContextBuilderInterface;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(MarshalGenerateNativeContextBuilderInterface::class)
            ->addTag('marshaller.context.native_context_builder.marshal_generate')
            ->addTag('proxy', ['interface' => MarshalGenerateNativeContextBuilderInterface::class]);

        $container->registerExtension(new MarshallerExtension());
        $container->addCompilerPass(new MarshallerPass());

        $container->setParameter('marshaller.cache_dir', sprintf('%s/marshaller', $container->getParameter('kernel.cache_dir')));
        $container->setParameter('marshaller.warmable_paths', []);
        $container->setParameter('marshaller.warmable_formats', ['json']);
        $container->setParameter('marshaller.warmable_nullable_data', false);
    }
}
