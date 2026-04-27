<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class OroDatagridEventLocator
{
    /** @var list<string> */
    public const DATAGRID_EVENT_PREFIXES = [
        'oro_datagrid.datagrid.build.after',
        'oro_datagrid.datagrid.build.before',
        'oro_datagrid.datagrid.build.pre',
        'oro_datagrid.grid_views_load',
        'oro_datagrid.orm_datasource.result.after',
        'oro_datagrid.orm_datasource.result.before',
        'oro_datagrid.orm_datasource.result.before_query',
    ];

    /**
     * @var list<array{event: string, class: string, method: string, file: string}>|null
     */
    private ?array $cache = null;

    public function __construct(
        private readonly OroBundleLocator $bundleLocator,
    ) {
    }

    /**
     * @return list<array{event: string, class: string, method: string, file: string}>
     */
    public function findAll(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $listeners = [];

        foreach ($this->bundleLocator->findAll() as $bundle) {
            $configDir = $bundle['directory'] . '/Resources/config';
            if (!is_dir($configDir)) {
                continue;
            }

            $finder = (new Finder())
                ->files()
                ->name(['*.yml', '*.yaml'])
                ->in($configDir);

            foreach ($finder as $file) {
                $content = $this->parseYamlFile($file->getRealPath());
                array_push($listeners, ...$this->extractListeners($content, $bundle['directory']));
            }
        }

        $this->cache = $listeners;

        return $listeners;
    }

    /**
     * @return list<array{event: string, class: string, method: string, file: string}>
     */
    public function findByDatagrid(string $datagridName): array
    {
        return array_values(array_filter(
            $this->findAll(),
            static fn(array $l) => str_ends_with($l['event'], '.' . $datagridName),
        ));
    }

    private function parseYamlFile(string $path): mixed
    {
        try {
            return Yaml::parseFile($path);
        } catch (ParseException) {
            return null;
        }
    }

    /**
     * @param mixed $content
     * @return list<array{event: string, class: string, method: string, file: string}>
     */
    private function extractListeners(mixed $content, string $bundleDirectory): array
    {
        if (!\is_array($content) || !\is_array($content['services'] ?? null)) {
            return [];
        }

        $listeners = [];

        foreach ($content['services'] as $serviceId => $definition) {
            if (!\is_array($definition)) {
                continue;
            }

            $class = $this->resolveClass($serviceId, $definition);

            if ($class === null) {
                continue;
            }

            foreach ($definition['tags'] ?? [] as $tag) {
                if (!\is_array($tag) || ($tag['name'] ?? '') !== 'kernel.event_listener') {
                    continue;
                }

                $event = (string) ($tag['event'] ?? '');

                if (!$this->isDatagridEvent($event)) {
                    continue;
                }

                $listeners[] = [
                    'event'  => $event,
                    'class'  => $class,
                    'method' => (string) ($tag['method'] ?? 'onEvent'),
                    'file'   => $this->resolveFile($class, $bundleDirectory),
                ];
            }
        }

        return $listeners;
    }

    /** @param array<string, mixed> $definition */
    private function resolveClass(string|int $serviceId, array $definition): ?string
    {
        if (isset($definition['class'])) {
            return (string) $definition['class'];
        }

        if (\is_string($serviceId) && str_contains($serviceId, '\\')) {
            return $serviceId;
        }

        return null;
    }

    private function isDatagridEvent(string $event): bool
    {
        foreach (self::DATAGRID_EVENT_PREFIXES as $prefix) {
            if (str_starts_with($event, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function resolveFile(string $class, string $bundleDirectory): string
    {
        $bundleName = basename($bundleDirectory);
        $parts      = explode('\\', $class);
        $idx        = array_search($bundleName, $parts, true);

        if ($idx !== false) {
            $subPath  = implode('/', \array_slice($parts, $idx + 1)) . '.php';
            $fullPath = $bundleDirectory . '/' . $subPath;

            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        return str_replace('\\', '/', $class) . '.php';
    }
}
