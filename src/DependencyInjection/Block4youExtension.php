<?php

namespace C4Y\Block4you\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
class Block4youExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // Set a default value for the path parameter.
        // This can be overridden in the project's config/parameters.yaml.
        if (!$container->hasParameter('block4you.path')) {
            $container->setParameter('block4you.path', 'bundles/block4you/element-sets');
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );
        $loader->load('services.yaml');
    }
}