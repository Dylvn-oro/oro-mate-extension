<?php

declare(strict_types=1);

/**
 * @author Dylan Trochain <dylvn-dev@pm.me>
 */

namespace Dylvn\OroMateExtension\Capability;

use Dylvn\OroMateExtension\Decoder\EntityConfigDecoder;
use Dylvn\OroMateExtension\Repository\EntityConfigRepositoryInterface;
use Dylvn\OroMateExtension\Repository\EntityFieldRepositoryInterface;
use Mcp\Capability\Attribute\McpTool;

final class EntityConfigTool
{
    public function __construct(
        private readonly EntityConfigRepositoryInterface $entityRepo,
        private readonly EntityFieldRepositoryInterface $fieldRepo,
        private readonly EntityConfigDecoder $decoder,
    ) {
    }

    #[McpTool(
        name: 'oro_entity_list',
        description: 'List OroCommerce entity configurations from the database. Pass a `search` string to filter by class name substring (e.g. "Product", "Customer").',
    )]
    public function listEntities(string $search = ''): string
    {
        try {
            $entities = $this->entityRepo->findAll();
        } catch (\RuntimeException $e) {
            return (string) json_encode(['error' => $e->getMessage()]);
        }

        if ($search !== '') {
            $entities = array_values(array_filter(
                $entities,
                static fn (array $e): bool => str_contains($e['class_name'], $search),
            ));
        }

        if ($entities === []) {
            if ($search !== '') {
                return \sprintf('No entities found matching "%s".', $search);
            }

            return 'No entity configurations found. Verify that oro_mate_extension.database_url is configured and that the OroCommerce database has been initialised.';
        }

        return (string) json_encode(
            [
                'count'    => \count($entities),
                'entities' => array_map(
                    static fn (array $e): array => [
                        'class_name' => $e['class_name'],
                    ],
                    $entities,
                ),
            ],
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES,
        );
    }

    #[McpTool(
        name: 'oro_entity_get',
        description: 'Get full config for an OroCommerce entity by exact class name, including decoded data and its field list (field_name, type, mode, is_extend). Use oro_entity_list to discover class names.',
    )]
    public function getEntity(string $class_name): string
    {
        try {
            $entity = $this->entityRepo->findByClassName($class_name);
        } catch (\RuntimeException $e) {
            return (string) json_encode(['error' => $e->getMessage()]);
        }

        if ($entity === null) {
            return (string) json_encode([
                'error' => \sprintf('Entity "%s" not found. Use oro_entity_list to browse available entities.', $class_name),
            ]);
        }

        try {
            $data   = $this->decoder->decode($entity['data']);
            $fields = $this->fieldRepo->findByEntityId($entity['id']);
        } catch (\RuntimeException $e) {
            return (string) json_encode(['error' => $e->getMessage(), 'class_name' => $class_name]);
        }

        return (string) json_encode(
            [
                'class_name' => $entity['class_name'],
                'data'       => $data,
                'fields'     => array_map(
                    static fn (array $f): array => [
                        'field_name' => $f['field_name'],
                        'type'       => $f['type'],
                    ],
                    $fields,
                ),
            ],
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES,
        );
    }

    #[McpTool(
        name: 'oro_entity_field_get',
        description: 'Get full config for a specific field of an OroCommerce entity, including decoded data. Use oro_entity_get to discover available field names.',
    )]
    public function getField(string $class_name, string $field_name): string
    {
        try {
            $entity = $this->entityRepo->findByClassName($class_name);
        } catch (\RuntimeException $e) {
            return (string) json_encode(['error' => $e->getMessage()]);
        }

        if ($entity === null) {
            return (string) json_encode([
                'error' => \sprintf('Entity "%s" not found. Use oro_entity_list to browse available entities.', $class_name),
            ]);
        }

        try {
            $field = $this->fieldRepo->findByEntityIdAndFieldName($entity['id'], $field_name);
        } catch (\RuntimeException $e) {
            return (string) json_encode(['error' => $e->getMessage(), 'class_name' => $class_name]);
        }

        if ($field === null) {
            return (string) json_encode([
                'error' => \sprintf('Field "%s" not found on entity "%s". Use oro_entity_get to list available fields.', $field_name, $class_name),
            ]);
        }

        try {
            $data = $this->decoder->decode($field['data']);
        } catch (\RuntimeException $e) {
            return (string) json_encode(['error' => $e->getMessage(), 'class_name' => $class_name, 'field_name' => $field_name]);
        }

        return (string) json_encode(
            [
                'class_name' => $class_name,
                'field_name' => $field['field_name'],
                'type'       => $field['type'],
                'data'       => $data,
            ],
            \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES,
        );
    }
}
