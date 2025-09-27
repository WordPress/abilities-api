# Contributing to the Abilities API feature plugin

Thank you for your interest in contributing to the Abilities API feature plugin! This contains all the documentation for getting started and contributing to the plugin and will eventually be a part of the [AI Team Handbook](https://make.wordpress.org/ai/handbook/).

## How to Contribute

Please [report (non-security) issues](https://github.com/WordPress/abilities/issues) and [open pull requests](https://github.com/WordPress/abilities-api/pulls) on GitHub. See below for information on reporting potential [security/privacy vulnerabilities](#reporting-security-issues).

Join the `#core-ai` channel [on WordPress Slack](http://wordpress.slack.com) ([sign up here](http://chat.wordpress.org)).

## Coding standards

In general, all code must follow the [WordPress Coding Standards and best practices](https://developer.wordpress.org/coding-standards/). All code in the Abilities API plugin must follow these requirements:

- **WordPress**: The plugin's minimum WordPress version requirement is 6.8.
- **PHP**: The minimum required version of the code slated for WordPress Core includes PHP 7.2, but the tooling and development environment requires PHP 7.4 or higher.

We include [several tools](#useful-commands) to help ensure your code meets contribution requirements.

## Guidelines

- As with all WordPress projects, we want to ensure a welcoming environment for everyone. With that in mind, all contributors are expected to follow our [Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/).

- All WordPress projects are [licensed under the GPLv2+](/LICENSE), and all contributions to Gutenberg will be released under the GPLv2+ license. You maintain copyright over any contribution you make, and by submitting a pull request, you are agreeing to release that contribution under the GPLv2+ license.

## Reporting Security Issues

Please see [SECURITY.md] (@TODO).

## Local Setup

### Prerequisites

- Node.js: 20.x (NVM recommended)
- Docker
- Git
- Composer: (if you prefer to run the Composer tools locally)

You can use Docker and the `wp-env` tool to set up a local development environment, instead of manually installing the specific testing versions of WordPress, PHP, and Composer. For more information, see the [wp-env documentation](https://developer.wordpress.org/block-editor/packages/packages-env/).

### Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/WordPress/abilities-api.git
   ```

2. Change into the project folder and install the development dependencies:

   ```bash
   ## If you're using nvm, make sure to use the correct Node.js version:
   nvm install && nvm use

   ## Then install the NPM dependencies:
   npm install

   # If you are using Composer locally, also run:
   composer install
   ```

3. Start the local development environment:
   ```bash
   npm run wp-env start
   ```

The WordPress development site will be available at http://localhost:8888 and the WP Admin Dashboard will be available at http://localhost:8888/wp-admin/. You can log in to the admin using the username `admin` and password `password`.

### Useful Commands

#### Installing Dependencies

- `composer install`: Install PHP dependencies.
- `npm install`: Install JavaScript dependencies.

#### Accessing the Local Environment

- `npm run wp-env start`: Start the local development environment.
- `npm run wp-env stop`: Stop the local development environment.
- `npm run wp-env run tests-cli YOUR_CMD_HERE`: Run WP-CLI commands in the local environment.

For more information on using `wp-env`, see the [wp-env documentation](https://developer.wordpress.org/block-editor/packages/packages-env/).

#### Linting and Formatting

##### PHP Code Quality

- `npm run lint:php`: Runs PHPCS linting on the PHP code.
- `npm run lint:php:fix`: Autofixes PHPCS linting issues.
- `npm run lint:php:stan`: Runs PHPStan static analysis.

##### JavaScript Code Quality

The project uses ESLint for linting and Prettier for code formatting, configured through `@wordpress/scripts`.

```bash
# Lint all JavaScript/TypeScript files
npm run lint:js

# Auto-fix linting issues
npm run lint:js:fix

# Run TypeScript typecheck
npm run typecheck
```

##### Formatting Code

Format all files (JavaScript, JSON, Markdown, etc.):

```bash
npm run format
```

### Running Tests

#### PHP Tests

PHPUnit tests can be run using the following command:

```bash
npm run test:php
```

To generate a code coverage report, make sure to start the testing environment with coverage mode enabled:

```bash
npm run env start -- --xdebug=coverage

npm run test:php
```

You should see the html coverage report in the `tests/_output/html` directory and the clover XML report in `tests/_output/php-coverage.xml`.

#### JavaScript Tests

The JavaScript client package tests can be run using the following commands:

```bash
# Run all JavaScript tests
npm run test:client

# Run tests in watch mode (great for development)
npm run test:client:watch
```

To generate a code coverage report, run:

```bash
npm run test:client:coverage
```

Coverage reports are generated in the `packages/client/coverage/` directory.

### Building the plugin for distribution

To build the plugin for distribution, you can use the following command:

```bash
npm run plugin-zip
```
