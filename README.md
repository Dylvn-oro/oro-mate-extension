# OroCommerce Extension for Symfony AI Mate

A [Symfony AI Mate](https://github.com/symfony/ai-mate) extension that exposes OroCommerce
datagrid configuration and entity config to AI assistants via MCP tools.

## Tools

### Datagrid tools

Read YAML configuration files directly from disk — no database connection required.

| Tool | Description |
|------|-------------|
| `oro_datagrid_list` | Lists all datagrid names discovered across all registered bundles. Accepts an optional `search` substring filter. |
| `oro_datagrid_get` | Returns the full merged definition for a given datagrid name: resolved mixins, source bundle/file pairs, and attached event listeners. |

### Entity config tools

Read from the OroCommerce PostgreSQL database (`oro_entity_config` and `oro_entity_config_field` tables).
**These tools only appear in the MCP tool list when `oro_mate_extension.database_url` is configured.**

| Tool | Description |
|------|-------------|
| `oro_entity_list` | Lists all entity configurations. Accepts an optional `search` substring filter on class name. |
| `oro_entity_get` | Returns full config for a single entity: decoded `data` array and a summary of its fields (`field_name`, `type`). |
| `oro_entity_field_get` | Returns full config for a specific field of an entity, including the decoded `data` array. |

---

## Installation

OroCommerce ships with pinned Symfony and PHP versions that typically conflict with
`symfony/ai-mate`. The recommended approach is to install this extension in a **standalone
workspace** that lives outside your OroCommerce project and reads its codebase from disk.

### 1. Create the workspace

```bash
mkdir ai-mate-workspace && cd ai-mate-workspace
composer init
```

> The exact packages required alongside this extension (e.g. `symfony/http-kernel`)
> may vary depending on which other Mate extensions you install and your
> environment. The commands below are a starting point. They may not work as-is.

```bash
composer require --dev dylvn/oro-mate-extension
```

The Mate composer plugin runs automatically during `composer install` and scaffolds
the `mate/` directory.

### 2. Configure the OroCommerce path

Override `oro_mate_extension.root_dir` in `mate/config.php` to point at your OroCommerce installation:

```php
$container->parameters()
    ->set('oro_mate_extension.root_dir', '/absolute/path/to/your/orocommerce')
;
```

### 3. (Optional) Enable entity config tools

Set `oro_mate_extension.database_url` to your OroCommerce PostgreSQL DSN. The entity tools
will then appear automatically in the MCP tool list.

```php
$container->parameters()
    ->set('oro_mate_extension.database_url', 'pgsql://user:password@localhost:5432/orocommerce')
;
```

### Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `oro_mate_extension.root_dir` | `%mate.root_dir%` | Absolute path to the OroCommerce application root |
| `oro_mate_extension.database_url` | *(empty)* | PostgreSQL DSN — enables entity config tools when set |

---

## How discovery works

### Datagrid tools

1. **Bundle discovery** — `OroBundleLocator` scans all `bundles.yml` files found under
   `Resources/config/oro/` to build the list of registered bundles and their directories.
2. **Datagrid discovery** — `OroDatagridLocator` iterates each bundle directory, parses
   every `datagrids.yml` / `datagrids.yaml` under `Resources/`, and merges definitions
   from multiple bundles into a single map.
3. **Event listener discovery** — `OroDatagridEventLocator` scans service YAML files for
   `kernel.event_listener` tags whose event name starts with a known `oro_datagrid.*` prefix.

Directories excluded from scanning: `cache`, `var`, `public`, `node_modules`, `tests`, `Tests`.

### Entity config tools

`EntityToolsAvailabilityPass` (a Symfony CompilerPass) runs at container compilation time.
When `oro_mate_extension.database_url` is empty it sets `mate.disabled_features` to hide the
three entity tools from `FilteredDiscoveryLoader`. When a DSN is present the pass is a no-op
and all tools are visible.

At query time `OroConnectionFactory` opens a lazy PDO connection to PostgreSQL.
`EntityConfigDecoder` decodes the `data` column stored as `base64(serialize($array))`.

---

## License

MIT
