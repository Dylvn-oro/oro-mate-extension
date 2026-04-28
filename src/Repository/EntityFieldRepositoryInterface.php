<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Repository;

interface EntityFieldRepositoryInterface
{
    /**
     * @return list<array{id: int, field_name: string, type: string, data: string|null}>
     */
    public function findByEntityId(int $entityId): array;

    /**
     * @return array{id: int, field_name: string, type: string, data: string|null}|null
     */
    public function findByEntityIdAndFieldName(int $entityId, string $fieldName): ?array;
}
