<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class EntityToolsAvailabilityPass implements CompilerPassInterface
{
    private const EXTENSION_NAME = 'dylvn/oro-mate-extension';
    private const ENTITY_TOOLS = ['oro_entity_list', 'oro_entity_get', 'oro_entity_field_get'];

    public function process(ContainerBuilder $container): void
    {
        $dbUrl = $container->hasParameter('oro_mate_extension.database_url')
            ? (string) $container->getParameter('oro_mate_extension.database_url')
            : '';

        if ($dbUrl !== '') {
            return;
        }

        $disabledFeatures = $container->hasParameter('mate.disabled_features')
            ? (array) $container->getParameter('mate.disabled_features')
            : [];

        foreach (self::ENTITY_TOOLS as $toolName) {
            $disabledFeatures[self::EXTENSION_NAME][$toolName] = ['enabled' => false];
        }

        $container->setParameter('mate.disabled_features', $disabledFeatures);
    }
}
