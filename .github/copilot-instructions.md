# WordPress Abilities API

The Abilities API is a WordPress plugin that provides a framework for registering and executing AI abilities in WordPress. It's built with PHP and uses Node.js tooling for development and testing.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Prerequisites and Setup
- Node.js 20+ (specified in `.nvmrc`)
- PHP 7.4+ (plugin supports 7.2+, but development tools require 7.4+)  
- Docker (for wp-env WordPress environment)
- Composer for PHP dependency management

### Essential Commands (VALIDATED)

**Install dependencies (15 seconds):**
```bash
npm ci                # Install Node.js dependencies (~13s)
composer install      # Install PHP dependencies (~1s)
```

**Code Quality and Linting (FAST - under 10 seconds each):**
```bash
# These work WITHOUT wp-env (use when Docker not available):
composer run-script lint      # Run PHPCS linting (~1s)
composer run-script format    # Auto-fix PHPCS issues (~1s) 
composer run-script phpstan   # Run PHPStan static analysis (~5s)
npm run format               # Format non-PHP files with Prettier (~1s)

# These require wp-env (use when WordPress environment is running):
npm run lint:php             # Same as composer lint, but via wp-env
npm run lint:php:fix         # Same as composer format, but via wp-env
npm run lint:php:stan        # Same as composer phpstan, but via wp-env
```

**Build plugin for distribution (FAST - under 1 second):**
```bash
npm run plugin-zip    # Create abilities-api.zip for distribution (~0.5s)
```

### WordPress Development Environment (wp-env)

**CRITICAL - wp-env startup timing:**
- **NEVER CANCEL wp-env commands** - they require network access and may take up to 10 minutes
- **Set timeout to 15+ minutes** for wp-env start commands
- CI uses 10-minute timeout with 3 retry attempts

**Start WordPress environment (NEVER CANCEL - up to 10 minutes):**
```bash
npm run wp-env start          # Start WordPress in Docker (~10 min first time, ~30s subsequent)
npm run wp-env start -- --xdebug=coverage  # Start with coverage enabled for testing
```

**Access the WordPress site:**
- Development site: http://localhost:8888
- Admin dashboard: http://localhost:8888/wp-admin/
- Username: `admin`
- Password: `password`

**WordPress environment commands:**
```bash
npm run wp-env stop           # Stop the WordPress environment
npm run wp-env -- run cli wp core version    # Check WordPress version
npm run wp-env -- run cli php -- -v          # Check PHP version
```

### Running Tests (REQUIRES wp-env)

**CRITICAL - PHPUnit test timing:**
- **NEVER CANCEL test commands** - they may take 10+ minutes across all PHP versions 
- **Set timeout to 15+ minutes** for test commands
- Tests require WordPress environment to be running

**Run PHPUnit tests (NEVER CANCEL - up to 15 minutes):**
```bash
npm run test:php              # Run PHPUnit tests (~5-15 min depending on setup)
```

**Run tests with coverage (for development):**
```bash
npm run wp-env start -- --xdebug=coverage  # Enable coverage first
npm run test:php              # Run tests with coverage reporting
```

Coverage reports are generated in:
- HTML report: `tests/_output/html/`
- XML report: `tests/_output/php-coverage.xml`

### Validation Scenarios

**ALWAYS validate changes by running through these scenarios:**

1. **Code Quality Validation (REQUIRED before commits):**
   ```bash
   composer run-script lint     # Must pass for CI
   composer run-script phpstan  # Must pass for CI  
   npm run format              # Must be clean for CI
   ```

2. **Plugin Functionality Test (when wp-env is available):**
   - Start wp-env and access WordPress admin
   - Navigate to `/wp-json/wp/v2/abilities` to verify REST API
   - Test ability registration via PHP:
     ```php
     wp_register_ability('test/example', [
         'label' => 'Test Ability',
         'description' => 'A test ability',
         'execute_callback' => function($input) { return $input; }
     ]);
     ```
   - Verify ability appears in `/wp-json/wp/v2/abilities` list
   - Test ability execution via `/wp-json/wp/v2/abilities/test%2Fexample/run` endpoint

3. **Build Validation:**
   ```bash
   npm run plugin-zip          # Must complete successfully
   ls -la abilities-api.zip    # Verify zip file created
   ```

## Working Without Docker/wp-env

If Docker is not available or network access is restricted, you can still work on the codebase effectively:

**Available commands (no Docker required):**
```bash
# Install dependencies
npm ci && composer install

# Code quality (all work without wp-env)
composer run-script lint      # PHPCS linting
composer run-script format    # PHPCS auto-fix
composer run-script phpstan   # Static analysis
npm run format               # Prettier formatting

# Build plugin
npm run plugin-zip           # Create distribution zip

# Syntax checking
php -l abilities-api.php     # Check PHP syntax
php -l includes/bootstrap.php
```

**NOT available without wp-env:**
- PHPUnit tests (require WordPress environment)
- WordPress functionality testing 
- REST API testing
- npm run lint:php* commands (these wrap the composer commands via wp-env)
- npm run test:php command

### Key Directories
- `abilities-api.php` - Main plugin file with WordPress plugin headers
- `includes/` - Core plugin code
  - `bootstrap.php` - Plugin initialization and class loading
  - `abilities-api.php` - Core API functions (wp_register_ability, etc.)
  - `abilities-api/` - Core classes (WP_Ability, WP_Abilities_Registry)
  - `rest-api/` - REST API controllers for /wp/v2/abilities endpoints
- `tests/` - PHPUnit tests (requires WordPress environment)
- `docs/` - User documentation files
- `vendor/` - Composer dependencies
- `node_modules/` - NPM dependencies

### Configuration Files
- `.wp-env.json` - WordPress environment configuration for Docker
- `phpunit.xml.dist` - PHPUnit test configuration
- `phpcs.xml.dist` - PHP CodeSniffer rules (WordPress standards + VIP + custom)
- `phpstan.neon.dist` - PHPStan static analysis configuration
- `composer.json` - PHP dependencies and scripts
- `package.json` - Node.js dependencies and npm scripts

## REST API Endpoints

When wp-env is running, the plugin provides these REST API endpoints:
- `GET /wp-json/wp/v2/abilities` - List all registered abilities
- `POST /wp-json/wp/v2/abilities/{name}/run` - Execute a specific ability

## Common Issues and Troubleshooting

**wp-env fails to start:**
- Ensure Docker is running and network access is available
- wp-env needs to download WordPress on first run
- May take up to 10 minutes - do not cancel
- If stuck, try `npm run wp-env clean` then `npm run wp-env start`

**Tests fail to run:**
- PHPUnit tests require WordPress environment (wp-env must be started first)
- Tests cannot run in isolation - they need WordPress constants and database

**Linting failures:**
- Run `composer run-script format` to auto-fix PHPCS issues
- Run `npm run format` to fix Prettier formatting issues
- Check `phpcs.xml.dist` for coding standards configuration

**CI failures:**
- All linting must pass: PHPCS, PHPStan, Prettier
- All tests must pass across PHP 7.4-8.4 and WordPress latest/trunk
- Plugin must build successfully with `npm run plugin-zip`

## WordPress Plugin Development Notes

- **Namespace prefixing:** All global functions/classes must use `wp_` or `WP_` prefix
- **Hook prefix:** Use `abilities_api_` for custom hooks  
- **Minimum WordPress version:** 6.8
- **Minimum PHP version:** 7.2 (but dev tools require 7.4+)
- **Text domain:** `abilities-api` (but current implementation omits text domain per i18n config)

## Performance Expectations

- **npm ci:** ~13 seconds
- **composer install:** ~1 second  
- **Code linting/formatting:** ~1-5 seconds each
- **wp-env start:** ~30 seconds (cached) to 10 minutes (first time) - NEVER CANCEL
- **PHPUnit tests:** ~5-15 minutes - NEVER CANCEL
- **Plugin zip build:** ~0.5 seconds

Always set appropriate timeouts and never cancel long-running operations - the CI system is designed around these timing expectations.