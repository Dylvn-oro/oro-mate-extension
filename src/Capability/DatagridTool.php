<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Capability;

use Dylvn\OroMateExtension\Service\OroDatagridEventLocator;
use Dylvn\OroMateExtension\Service\OroDatagridLocator;
use Mcp\Capability\Attribute\McpTool;

final class DatagridTool
{
    public function __construct(
        private readonly OroDatagridLocator $locator,
        private readonly OroDatagridEventLocator $eventLocator,
    ) {
    }

    #[McpTool(
        name: 'oro_datagrid_get',
        description: 'Get the full definition of an OroCommerce datagrid by its exact name. Returns name, merged definition (including resolved mixins), attached events listeners, source bundle/file pairs, and mixin names.',
    )]
    public function getDefinition(string $name): string
    {
        $result = $this->locator->findByName($name);

        if ($result === null) {
            return (string) json_encode([
                'error' => \sprintf('Datagrid "%s" not found. Use oro_datagrid_list to browse available datagrids.', $name),
            ]);
        }

        $definition = $result['definition'];

        $mixinNames = [];
        if (isset($definition['extends']) && \is_string($definition['extends'])) {
            $mixinNames[] = $definition['extends'];
        }
        if (isset($definition['mixins']) && \is_array($definition['mixins'])) {
            foreach ($definition['mixins'] as $mixin) {
                $mixinNames[] = (string) $mixin;
            }
        }

        $resolvedDefinition = [];
        foreach ($mixinNames as $mixinName) {
            $mixin = $this->locator->findByName($mixinName);
            if ($mixin !== null) {
                $resolvedDefinition = array_replace_recursive($resolvedDefinition, $mixin['definition']);
            }
        }
        $resolvedDefinition = array_replace_recursive($resolvedDefinition, $definition);

        return (string) json_encode([
            'name'            => $name,
            'definition'      => $resolvedDefinition,
            'sources'         => $result['sources'],
            'event_listeners' => $this->eventLocator->findByDatagrid($name),
            'mixins'          => $mixinNames,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);
    }

    #[McpTool(
        name: 'oro_datagrid_list',
        description: 'List OroCommerce datagrid names. Pass a `search` string to filter results by substring (e.g. "product", "order"). Returns names and their source bundles.',
    )]
    public function listDatagrids(string $search = ''): string
    {
        $datagrids = $this->locator->search($search);

        if ($datagrids === []) {
            return $search !== ''
                ? \sprintf('No datagrids found matching "%s".', $search)
                : 'No datagrids found. Verify that `oro_mate_extension.root_dir` parameter is set to a valid OroCommerce installation.';
        }

        $count  = \count($datagrids);
        $header = $search !== ''
            ? \sprintf("Found %d datagrid(s) matching \"%s\":\n\n", $count, $search)
            : \sprintf("Found %d datagrid(s):\n\n", $count);

        $lines = [];
        foreach ($datagrids as $dgName => $data) {
            $bundles = array_column($data['sources'], 'bundle');
            $lines[] = \sprintf('%s  (%s)', $dgName, implode(', ', $bundles));
        }

        return $header . implode("\n", $lines);
    }
}
