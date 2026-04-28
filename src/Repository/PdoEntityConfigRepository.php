<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Repository;

use Dylvn\OroMateExtension\Database\OroConnectionFactory;

final class PdoEntityConfigRepository implements EntityConfigRepositoryInterface
{
    public function __construct(private readonly OroConnectionFactory $factory)
    {
    }

    public function findAll(): array
    {
        $stmt = $this->factory->getConnection()->query(
            'SELECT id, class_name, data FROM oro_entity_config ORDER BY class_name ASC',
        );

        return array_map($this->normalizeRow(...), $stmt->fetchAll());
    }

    public function findByClassName(string $className): ?array
    {
        $stmt = $this->factory->getConnection()->prepare(
            'SELECT id, class_name, data FROM oro_entity_config WHERE class_name = ? LIMIT 1',
        );
        $stmt->execute([$className]);

        $row = $stmt->fetch();

        return $row !== false ? $this->normalizeRow($row) : null;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: int, class_name: string, data: string|null}
     */
    private function normalizeRow(array $row): array
    {
        return [
            'id'         => (int) $row['id'],
            'class_name' => (string) $row['class_name'],
            'data'       => $row['data'] !== null ? (string) $row['data'] : null,
        ];
    }
}
