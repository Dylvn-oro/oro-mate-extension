<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

use Dylvn\OroMateExtension\Capability\DatagridTool;
use Dylvn\OroMateExtension\Service\OroBundleLocator;
use Dylvn\OroMateExtension\Service\OroDatagridEventLocator;
use Dylvn\OroMateExtension\Service\OroDatagridLocator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    // Parameters
    $container->parameters()
        ->set('oro_mate_extension.root_dir', '%mate.root_dir%');

    $services = $container->services();

    $services->set(OroBundleLocator::class)
        ->args(['%oro_mate_extension.root_dir%']);

    $services->set(OroDatagridLocator::class)
        ->args([service(OroBundleLocator::class)]);

    $services->set(OroDatagridEventLocator::class)
        ->args([service(OroBundleLocator::class)]);

    $services->set(DatagridTool::class)
        ->args([
            service(OroDatagridLocator::class),
            service(OroDatagridEventLocator::class),
        ]);
};
