# OroCommerce Datagrid & Entity Config Extension

This extension exposes OroCommerce datagrid configuration and entity config to AI assistants.
Datagrid tools scan an OroCommerce codebase on disk. Entity config tools require a PostgreSQL database connection.

## Available tools

### `oro_datagrid_list`

Lists all available OroCommerce datagrid names discovered across all registered bundles.

**Parameters:**
- `search` *(optional)*: Filter results by substring (e.g. `"product"`, `"order"`).

**Returns:** A list of datagrid names with their source bundle(s).

### `oro_datagrid_get`

Returns the full definition of a single datagrid by its exact name.

**Parameters:**
- `name` *(required)*: The exact datagrid name (use `oro_datagrid_list` to discover names).

**Returns:** The merged datagrid definition (mixins resolved), list of source bundle/file pairs, attached event listeners, and mixin names.

## Setup

Set the `ORO_APPLICATION_PATH` environment variable to the absolute path of the OroCommerce application root before starting the Mate server.

```bash
export ORO_APPLICATION_PATH=/path/to/orocommerce
```

## Workflow tips

- Start with `oro_datagrid_list` to find the datagrid you want to inspect.
- Use `oro_datagrid_get` with the exact name to get the full merged definition including mixin resolution.
- The `event_listeners` field in `oro_datagrid_get` shows PHP listeners attached to that specific datagrid.

---

## Entity Configuration Tools

These tools read from the OroCommerce PostgreSQL database (`oro_entity_config` and `oro_entity_config_field` tables).
They require `oro_mate_extension.database_url` to be set in your `mate/config.php`:

```php
// mate/config.php
$container->parameters()->set('oro_mate_extension.database_url', 'pgsql://user:password@localhost:5432/orocommerce');
```

If not configured, the tools are still available but return a configuration error.

### `oro_entity_list`

Lists all OroCommerce entity configurations from the database.

**Parameters:**
- `search` *(optional)*: Filter results by class name substring (e.g. `"Product"`, `"Customer"`).

**Returns:** JSON with `count` and an `entities` array — each entry has `class_name`.

### `oro_entity_get`

Returns full config for a single entity, including decoded `data` and a summary of its fields.

**Parameters:**
- `class_name` *(required)*: Exact class name (use `oro_entity_list` to discover).

**Returns:** JSON with `class_name`, `data` (decoded config array), and `fields` (array of `field_name`, `type` — no field data, to keep the response compact).

### `oro_entity_field_get`

Returns full config for a specific field of an entity, including decoded `data`.

**Parameters:**
- `class_name` *(required)*: Exact entity class name.
- `field_name` *(required)*: Exact field name (use `oro_entity_get` to discover).

**Returns:** JSON with `class_name`, `field_name`, `type`, `data` (decoded field config array).

## Entity config workflow tips

- Use `oro_entity_list` to browse. The `search` parameter accepts bundle-name fragments like `"Product"` or `"Order"`.
- Use `oro_entity_get` to inspect a single entity and see what fields it has.
- Use `oro_entity_field_get` only when you need the full `data` payload for a specific field.
