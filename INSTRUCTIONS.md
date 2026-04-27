# OroCommerce Datagrid Extension

This extension exposes OroCommerce datagrid configuration to AI assistants.
It scans an OroCommerce codebase on disk — no database connection is required.

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
