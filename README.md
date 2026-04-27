# OroCommerce Extension for Symfony AI Mate

A [Symfony AI Mate](https://github.com/symfony/ai-mate) extension that exposes OroCommerce
datagrid configuration to AI assistants via two MCP tools.

Reads YAML configuration files directly from disk — **no database connection required**.

## Tools

| Tool | Description |
|------|-------------|
| `oro_datagrid_list` | Lists all datagrid names discovered across all registered bundles. Accepts an optional `search` substring filter. |
| `oro_datagrid_get` | Returns the full merged definition for a given datagrid name: resolved mixins, source bundle/file pairs, and attached event listeners. |

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

The extension exposes a `oro_mate_extension.root_dir` parameter (defaults to `%mate.root_dir%`).
Override it in `mate/config.php` to point at your OroCommerce installation:

```php
$container->parameters()
    ->set('oro_mate_extension.root_dir', '/absolute/path/to/your/orocommerce')
;
```

### Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `oro_mate_extension.root_dir` | `%mate.root_dir%` | Absolute path to the OroCommerce application root |

---

## How discovery works

1. **Bundle discovery** — `OroBundleLocator` scans all `bundles.yml` files found under
   `Resources/config/oro/` to build the list of registered bundles and their directories.
2. **Datagrid discovery** — `OroDatagridLocator` iterates each bundle directory, parses
   every `datagrids.yml` / `datagrids.yaml` under `Resources/`, and merges definitions
   from multiple bundles into a single map.
3. **Event listener discovery** — `OroDatagridEventLocator` scans service YAML files for
   `kernel.event_listener` tags whose event name starts with a known `oro_datagrid.*` prefix.

Directories excluded from scanning: `cache`, `var`, `public`, `node_modules`, `tests`, `Tests`.

---

## License

MIT
