<?php

namespace C4Y\Block4you\ContaoManager;

use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\CoreBundle\ContaoCoreBundle;
use C4Y\Block4you\Block4youBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{

    // dies hier ist obligatorisch
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(Block4youBundle::class)
                ->setLoadAfter(
                    [ContaoCoreBundle::class]
                )
        ];
    }

    // dies hier wird nur benötigt, wenn eigene Routen außerhalb von Contao benötigt
    // werden, z.B. für eine eigene API (ließe sich aber auch mit Modulen oder Inhalts-
    // elementen lösen) oder Ajax-Requests
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver
            ->resolve(__DIR__ . '/../../config/routing.yaml')
            ->load(__DIR__ . '/../../config/routing.yaml');
    }
}
