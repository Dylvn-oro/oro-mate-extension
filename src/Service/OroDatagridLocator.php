<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class OroDatagridLocator
{
    /**
     * @var array<string, array{
     *   definition: mixed[],
     *   sources: list<array{bundle: string, path: string}>
     * }>|null
     */
    private ?array $cache = null;

    public function __construct(
        private readonly OroBundleLocator $bundleLocator,
    ) {
    }

    /**
     * @return array<string, array{
     *   definition: mixed[],
     *   sources: list<array{bundle: string, path: string}>
     * }>
     */
    public function findAll(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $datagrids = [];

        foreach ($this->bundleLocator->findAll() as $bundle) {
            if (!is_dir($bundle['directory'])) {
                continue;
            }

            $finder = (new Finder())
                ->files()
                ->name(['datagrids.yml', 'datagrids.yaml'])
                ->in($bundle['directory'])
                ->path('Resources');

            foreach ($finder as $file) {
                try {
                    $content = Yaml::parseFile($file->getRealPath());
                } catch (ParseException) {
                    continue;
                }

                $source = ['bundle' => $bundle['name'], 'path' => $file->getRealPath()];

                foreach ($content['datagrids'] ?? [] as $name => $definition) {
                    $name       = (string) $name;
                    $definition = (array) $definition;

                    if (isset($datagrids[$name])) {
                        $datagrids[$name] = [
                            'definition' => array_replace_recursive($datagrids[$name]['definition'], $definition),
                            'sources'    => [...$datagrids[$name]['sources'], $source],
                        ];
                    } else {
                        $datagrids[$name] = [
                            'definition' => $definition,
                            'sources'    => [$source],
                        ];
                    }
                }
            }
        }

        ksort($datagrids);
        $this->cache = $datagrids;

        return $datagrids;
    }

    /**
     * @return array{definition: mixed[], sources: list<array{bundle: string, path: string}>}|null
     */
    public function findByName(string $name): ?array
    {
        return $this->findAll()[$name] ?? null;
    }

    /**
     * @return array<string, array{definition: mixed[], sources: list<array{bundle: string, path: string}>}>
     */
    public function search(string $pattern): array
    {
        $all = $this->findAll();

        if ($pattern === '') {
            return $all;
        }

        return array_filter(
            $all,
            static fn(string $k) => str_contains($k, $pattern),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /** @return string[] */
    public function getNames(): array
    {
        return array_keys($this->findAll());
    }
}
