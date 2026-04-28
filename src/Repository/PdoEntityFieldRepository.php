<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Repository;

use Dylvn\OroMateExtension\Database\OroConnectionFactory;

final class PdoEntityFieldRepository implements EntityFieldRepositoryInterface
{
    public function __construct(private readonly OroConnectionFactory $factory)
    {
    }

    public function findByEntityId(int $entityId): array
    {
        $stmt = $this->factory->getConnection()->prepare(
            'SELECT id, field_name, type, data FROM oro_entity_config_field WHERE entity_id = ? ORDER BY field_name ASC',
        );
        $stmt->execute([$entityId]);

        return array_map($this->normalizeRow(...), $stmt->fetchAll());
    }

    public function findByEntityIdAndFieldName(int $entityId, string $fieldName): ?array
    {
        $stmt = $this->factory->getConnection()->prepare(
            'SELECT id, field_name, type, data FROM oro_entity_config_field WHERE entity_id = ? AND field_name = ? LIMIT 1',
        );
        $stmt->execute([$entityId, $fieldName]);

        $row = $stmt->fetch();

        return $row !== false ? $this->normalizeRow($row) : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, field_name: string, type: string, data: string|null}
     */
    private function normalizeRow(array $row): array
    {
        return [
            'id'         => (int) $row['id'],
            'field_name' => (string) $row['field_name'],
            'type'       => (string) $row['type'],
            'data'       => $row['data'] !== null ? (string) $row['data'] : null,
        ];
    }
}
