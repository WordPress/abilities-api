# WordPress Abilities API - Developer Instructions

The Abilities API is a WordPress plugin that provides a framework for registering and executing AI abilities. This file provides GitHub Copilot with essential context for working effectively in this codebase.

## Quick Reference

**Project Overview**: See [README.md](../README.md) for project scope, goals, and current status.

**Setup & Contribution Guidelines**: See [CONTRIBUTING.md](../CONTRIBUTING.md) for:
- Prerequisites (Node.js 20+, Docker, PHP 7.4+)
- Installation steps (`npm install`, `composer install`)
- Local development environment setup with wp-env
- Available commands and scripts

**Project Documentation**: See [docs/](../docs/) for:
- [Introduction & concepts](../docs/1.intro.md) - Core concepts, goals, use cases
- [Getting started guide](../docs/2.getting-started.md) - Installation options
- [Registering abilities](../docs/3.registering-abilities.md) - API usage
- [Using abilities](../docs/4.using-abilities.md) - Execution patterns

## Repository Structure

```
abilities-api/
├── abilities-api.php          # Main plugin file
├── includes/                  # Core plugin code
│   ├── bootstrap.php         # Plugin initialization
│   ├── abilities-api.php     # Core API functions
│   ├── abilities-api/        # Core classes (WP_Ability, WP_Abilities_Registry)
│   └── rest-api/            # REST API controllers (/wp/v2/abilities)
├── tests/                   # PHPUnit tests
├── docs/                    # User documentation
├── CONTRIBUTING.md          # Development setup & guidelines
├── package.json            # npm scripts & dependencies
├── composer.json           # PHP dependencies & scripts
├── phpcs.xml.dist          # Code standards configuration
├── phpstan.neon.dist       # Static analysis configuration
├── phpunit.xml.dist        # Test configuration
└── .wp-env.json           # WordPress environment configuration
```

## Essential Commands

**Dependency Installation**:
```bash
npm ci && composer install
```

**Code Quality** (use these - they work without Docker):
```bash
composer run-script lint      # PHPCS linting
composer run-script format    # Auto-fix PHPCS issues
composer run-script phpstan   # Static analysis
npm run format               # Format non-PHP files
```

**WordPress Environment** (Docker-based, see CONTRIBUTING.md for details):
```bash
npm run wp-env start    # Start WordPress at http://localhost:8888
npm run test:php        # Run PHPUnit tests
```

**Build**:
```bash
npm run plugin-zip     # Create distribution package
```

## Critical Development Notes

**⚠️ TIMING WARNING**: wp-env commands can take up to 10 minutes on first run due to WordPress downloads. Set timeout to 15+ minutes and never cancel these operations.

**⚠️ DOCKER DEPENDENCY**: PHPUnit tests and `npm run lint:php*` commands require wp-env. Use direct composer commands when Docker is unavailable.

**Code Standards**: All code must follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/). Use `composer run-script format` to auto-fix issues.

**WordPress Integration**:
- Minimum WordPress: 6.8
- Minimum PHP: 7.2 (dev tools require 7.4+)
- Function/class prefix: `wp_` or `WP_`
- Hook prefix: `abilities_api_`

## REST API

When wp-env is running, test these endpoints:
- `GET /wp-json/wp/v2/abilities` - List registered abilities
- `POST /wp-json/wp/v2/abilities/{name}/run` - Execute specific ability

## Configuration Reference

**Package Scripts**: See `package.json` "scripts" section for all available npm commands.

**PHP Dependencies**: See `composer.json` "scripts" section for Composer commands.

**Code Standards**: See `phpcs.xml.dist` for PHPCS rules (WordPress + VIP + custom standards).

**Static Analysis**: See `phpstan.neon.dist` for PHPStan configuration.

**Testing**: See `phpunit.xml.dist` for test suite configuration.

**WordPress Environment**: See `.wp-env.json` for Docker environment setup.

For detailed usage instructions, troubleshooting, and development workflows, always refer to the source documentation files listed above rather than relying on these abbreviated notes.