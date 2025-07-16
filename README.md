# Abilities API  
[_Part of the **AI Building Blocks for WordPress** initiative_
](https://make.wordpress.org/ai/2025/07/17/ai-building-blocks)
## Overview
* **Purpose:** provide a common way for WordPress core, plugins, and themes to describe what they can do (“abilities”) in a machine‑readable, human‑friendly form.  
* **Scope:** discovery, permissioning, and execution metadata only. Actual business logic stays inside the registering component.  
* **Audience:** plugin & theme authors, agency builders, and future AI / automation tools.

## Design Goals
1. **Discoverability** – every ability can be listed, queried, and inspected.  
2. **Interoperability** – a uniform schema lets unrelated components compose workflows.  
3. **Security‑first** – explicit permissions determine who/what may invoke an ability.  
4. **Gradual adoption** – ships first as a Composer package, migrates smoothly to core.

## Inspiration
* **[wp‑feature‑api](https://github.com/automattic/wp-feature-api)** – shared vision of declaring capabilities at the PHP layer.  
* Command Palette experiments in Gutenberg.  
* Modern AI assistant protocols (MCP, A2A).

## Current Status
| Milestone | State |
|-----------|-------|
| Placeholder repository | **created** |
| Spec draft | in progress |
| Prototype plugin & Composer package | planned |
| Community feedback (#core‑ai Slack) | planned |
| Core proposal | planned |

## How to Get Involved
* **Discuss:** `#core-ai` channel on WordPress Slack.  
* **File issues:** suggestions & use‑cases welcome in this repo.  
* **Prototype:** experiment with the upcoming Composer package once released.

## License
GPL-3
