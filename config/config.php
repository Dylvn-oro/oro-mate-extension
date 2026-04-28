<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

use Dylvn\OroMateExtension\Capability\DatagridTool;
use Dylvn\OroMateExtension\Capability\EntityConfigTool;
use Dylvn\OroMateExtension\Database\OroConnectionFactory;
use Dylvn\OroMateExtension\Decoder\EntityConfigDecoder;
use Dylvn\OroMateExtension\DependencyInjection\EntityToolsAvailabilityPass;
use Dylvn\OroMateExtension\Repository\EntityConfigRepositoryInterface;
use Dylvn\OroMateExtension\Repository\EntityFieldRepositoryInterface;
use Dylvn\OroMateExtension\Repository\PdoEntityConfigRepository;
use Dylvn\OroMateExtension\Repository\PdoEntityFieldRepository;
use Dylvn\OroMateExtension\Service\OroBundleLocator;
use Dylvn\OroMateExtension\Service\OroDatagridEventLocator;
use Dylvn\OroMateExtension\Service\OroDatagridLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container, ContainerBuilder $builder): void {
    // Parameters
    $container->parameters()
        ->set('oro_mate_extension.root_dir', '%mate.root_dir%')
        ->set('oro_mate_extension.database_url', '');

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

    // Entity config tools — require oro_mate_extension.database_url to be set in mate/config.php
    $services->set(OroConnectionFactory::class)
        ->args(['%oro_mate_extension.database_url%']);

    $services->set(EntityConfigDecoder::class);

    $services->set(PdoEntityConfigRepository::class)
        ->args([service(OroConnectionFactory::class)]);

    $services->set(PdoEntityFieldRepository::class)
        ->args([service(OroConnectionFactory::class)]);

    $services->alias(EntityConfigRepositoryInterface::class, PdoEntityConfigRepository::class);
    $services->alias(EntityFieldRepositoryInterface::class, PdoEntityFieldRepository::class);

    $services->set(EntityConfigTool::class)
        ->args([
            service(EntityConfigRepositoryInterface::class),
            service(EntityFieldRepositoryInterface::class),
            service(EntityConfigDecoder::class),
        ]);

    $builder->addCompilerPass(new EntityToolsAvailabilityPass());
};
