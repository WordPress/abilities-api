# WordPress Abilities API - Barebones Instructions

This repository contains the WordPress Abilities API plugin. For comprehensive development guidance, refer to the existing project documentation.

## Repository Structure

```
abilities-api/
├── abilities-api.php          # Main plugin file
├── includes/                  # Core plugin code
│   ├── bootstrap.php         # Plugin initialization
│   ├── abilities-api.php     # Core API functions
│   ├── abilities-api/        # Core classes (WP_Ability, Registry)
│   └── rest-api/            # REST controllers (/wp/v2/abilities)
├── tests/                   # PHPUnit tests
├── docs/                    # User documentation
├── .wp-env.json            # WordPress environment config
├── phpcs.xml.dist          # PHP CodeSniffer standards
├── phpstan.neon.dist       # Static analysis config
├── composer.json           # PHP dependencies and scripts
└── package.json            # Node.js dependencies and scripts
```

## Documentation Sources

**Development Setup**: See `CONTRIBUTING.md` for complete setup instructions
**User Guide**: See `/docs` directory for plugin usage documentation  
**Code Standards**: Configuration in `phpcs.xml.dist` (WordPress + VIP standards)
**Static Analysis**: Configuration in `phpstan.neon.dist`
**WordPress Environment**: Configuration in `.wp-env.json`
**Testing**: Configuration in `phpunit.xml.dist`

## Essential Commands

**Dependencies**: Check `package.json` and `composer.json` scripts sections
**Development Environment**: Uses wp-env (Docker) - see `.wp-env.json`
**Code Quality**: PHPCS, PHPStan, Prettier - see configuration files
**Testing**: PHPUnit with WordPress test environment
**Building**: Plugin zip creation for distribution

## Quick Reference

- **PHP Version**: 7.2+ (development requires 7.4+)
- **WordPress Version**: 6.8+  
- **Text Domain**: `abilities-api`
- **Namespace Prefix**: `wp_` for functions, `WP_` for classes
- **Hook Prefix**: `abilities_api_` for custom hooks

## REST API Endpoints

When WordPress environment is running:
- `GET /wp-json/wp/v2/abilities` - List registered abilities
- `POST /wp-json/wp/v2/abilities/{name}/run` - Execute ability

Refer to source code in `includes/rest-api/` for implementation details.