<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Marshaller\DependencyInjection\MarshallerExtension;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerExtension(new MarshallerExtension());

        $container->setParameter('marshaller.cache_dir', sprintf('%s/marshaller', $container->getParameter('kernel.cache_dir')));
        $container->setParameter('marshaller.warmable_paths', []);
        $container->setParameter('marshaller.warmable_formats', ['json']);
        $container->setParameter('marshaller.warmable_nullable_data', false);
    }
}
