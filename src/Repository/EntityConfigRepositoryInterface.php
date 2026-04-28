<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Repository;

interface EntityConfigRepositoryInterface
{
    /**
     * @return list<array{id: int, class_name: string, data: string|null}>
     */
    public function findAll(): array;

    /**
     * @return array{id: int, class_name: string, data: string|null}|null
     */
    public function findByClassName(string $className): ?array;
}
