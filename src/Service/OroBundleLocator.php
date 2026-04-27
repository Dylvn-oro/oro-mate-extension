<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class OroBundleLocator
{
    /** @var list<array{name: string, directory: string}>|null */
    private ?array $cache = null;

    public function __construct(
        private readonly string $applicationPath,
    ) {
    }

    /**
     * Returns all bundles ordered by priority ascending (lowest first), ties broken alphabetically.
     *
     * @return list<array{name: string, directory: string}>
     */
    public function findAll(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $finder = (new Finder())
            ->files()
            ->name('bundles.yml')
            ->in($this->applicationPath)
            ->exclude(['cache', 'var', 'public', 'node_modules', 'tests', 'Tests'])
            ->path('Resources/config/oro');

        /** @var list<array{name: string, directory: string, priority: int}> $entries */
        $entries = [];

        foreach ($finder as $file) {
            try {
                $content = Yaml::parseFile($file->getRealPath());
            } catch (ParseException) {
                continue;
            }

            $directory = dirname($file->getRealPath(), 4);

            foreach ($content['bundles'] ?? [] as $bundle) {
                if (!isset($bundle['name'])) {
                    continue;
                }

                $entries[] = [
                    'name'      => (string) $bundle['name'],
                    'directory' => $directory,
                    'priority'  => (int) ($bundle['priority'] ?? 0),
                ];
            }
        }

        usort($entries, static function (array $a, array $b): int {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority'];
            }

            return strcasecmp($a['name'], $b['name']);
        });

        $this->cache = array_map(
            static fn(array $e) => ['name' => $e['name'], 'directory' => $e['directory']],
            $entries,
        );

        return $this->cache;
    }

    /** @return list<string> */
    public function getBundleNames(): array
    {
        return array_column($this->findAll(), 'name');
    }

    /** @return list<string> */
    public function getBundleDirectories(): array
    {
        return array_column($this->findAll(), 'directory');
    }
}
