<?php
/**
 * Abilities API
 *
 * @package     abilities-api
 * @author      WordPress.org Contributors
 * @copyright   2025 Plugin Contributors
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Abilities API
 * Plugin URI:        https://github.com/WordPress/abilities-api
 * Description:       Provides a framework for registering and executing AI abilities in WordPress.
 * Requires at least: 6.8
 * Version:           0.1.0
 * Requires PHP:      7.2
 * Author:            WordPress.org Contributors
 * Author URI:        https://github.com/WordPress/abilities-api/graphs/contributors
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       abilities-api
 */

/**
 * Shortcut constant to the path of this file.
 */
define( 'WP_ABILITIES_API_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Version of the plugin.
 */
define( 'WP_ABILITIES_API_VERSION', '0.1.0' );

/**
 * First the WP_Ability class that users can extend.
 */
require_once WP_ABILITIES_API_DIR . 'includes/abilities-api/class-wp-ability.php';

/**
 * Then the WP_Abilities_Registry class that manages the abilities.
 */
require_once WP_ABILITIES_API_DIR . 'includes/abilities-api/class-wp-abilities-registry.php';

/**
 * Then the public access functions that users can use to interact with the abilities.
 */
require_once WP_ABILITIES_API_DIR . 'includes/abilities-api.php';

/**
 * Initialize REST API controllers.
 */
require_once WP_ABILITIES_API_DIR . 'includes/rest-api/class-wp-rest-abilities-init.php';
