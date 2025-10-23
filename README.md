# Abilities API

[_Part of the **AI Building Blocks for WordPress** initiative_
](https://make.wordpress.org/ai/2025/07/17/ai-building-blocks)

[Handbook](https://make.wordpress.org/ai/handbook/projects/abilities-api/)

## Overview

- **Purpose:** provide a common way for WordPress core, plugins, and themes to describe what they can do ("abilities") in a machine‑readable, human‑friendly form.
- **Scope:** discovery, permissioning, and execution metadata only. Actual business logic stays inside the registering component.
- **Audience:** plugin & theme authors, agency builders, and future AI / automation tools.

## Design Goals

1. **Discoverability** - every ability can be listed, queried, and inspected.
2. **Interoperability** - a uniform schema lets unrelated components compose workflows.
3. **Security‑first** - explicit permissions determine who/what may invoke an ability.
4. **Gradual adoption** - ships first as a Composer package, migrates smoothly to core.

## Documentation

- **[Developer docs](docs/README.md)**.
- **[Contributing Guidelines](CONTRIBUTING.md)**.

## Inspiration

- **[wp‑feature‑api](https://github.com/automattic/wp-feature-api)** - shared vision of declaring capabilities at the PHP layer.
- Command Palette experiments in Gutenberg.
- Modern AI assistant protocols (MCP, A2A).

## Current Status

| Milestones                          | State       |
| ----------------------------------- | ----------- |
| Placeholder repository              | **created** |
| Spec draft                          | **created** |
| Prototype plugin & Composer package | **created** |
| Community feedback (#core‑ai Slack) | **created** |
| Core proposal                       | in progress |
| Initial                             | **created** |
| WordPress 6.9                       | in progress |

## How to Get Involved

- **Discuss:** `#core-ai` channel on WordPress Slack.
- **File issues:** suggestions & use‑cases welcome in this repo.
- **Prototype:** experiment with the [feature plugin](https://github.com/WordPress/abilities-api/releases/latest) or the [`wordpress/abilities-api`](https://packagist.org/packages/wordpress/abilities-api) Composer package.

## License

WordPress is free software, and is released under the terms of the GNU General Public License version 2 or (at your option) any later version. See [LICENSE.md](LICENSE.md) for complete license.

<br/><br/><p align="center"><img src="https://s.w.org/style/images/codeispoetry.png?1" alt="Code is Poetry." /></p>
