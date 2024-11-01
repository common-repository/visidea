<?php
/**
 * @wordpress-plugin
 * Plugin Name: Visidea
 * Plugin URI: https://visidea.ai
 * Description: Visidea is the search and recommendations plugin for WooCommerce. Visidea improves UX and increases the revenues of your website.
 * Version: 2.1.8
 * Author: Inferendo
 * Author URI: https://visidea.ai
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: visidea
 * @package Visidea
 */

// If this file is called directly, abort.
if (!defined( 'WPINC')) {
	die;
}

/**
 * Require action scheduler.
 */
require_once( plugin_dir_path( __FILE__ ) . 'libraries/action-scheduler/action-scheduler.php' );

/**
 * Current Visidea version.
 */
define('VISIDEA_VERSION', '2.1.8');

/**
 * The code that runs during Visidea activation.
 * This action is documented in includes/class-visidea-activator.php
 */
function activate_visidea() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-visidea-activator.php';
    Visidea_Activator::activate();
}
register_activation_hook( __FILE__, 'activate_visidea' );

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_visidea() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-visidea-deactivator.php';
	Visidea_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'deactivate_visidea' );

/**
 * The core Visidea class that is used to define admin-specific hooks
 * and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-visidea.php';

/**
 * Begins execution of Visidea.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_visidea() {
	$plugin = new Visidea();
	$plugin->run();
}

/**
 * A callback to run when the scheduled action is run.
 */
function visidea_export_data_job() {
// 	$plugin_admin = new Visidea_Admin('visidea', VISIDEA_VERSION);
// 	$plugin_admin->export_data();
}
add_action( 'visidea_export_data_job', 'visidea_export_data_job' );

function visidea_export_items_job() {
	$plugin_admin = new Visidea_Admin('visidea', VISIDEA_VERSION);
	$plugin_admin->cron_items_dump();
}
add_action( 'visidea_export_items_job', 'visidea_export_items_job' );

function visidea_export_users_job() {
	$plugin_admin = new Visidea_Admin('visidea', VISIDEA_VERSION);
	$plugin_admin->cron_users_dump();
}
add_action( 'visidea_export_users_job', 'visidea_export_users_job' );

function visidea_export_interactions_job() {
	$plugin_admin = new Visidea_Admin('visidea', VISIDEA_VERSION);
	$plugin_admin->cron_interactions_dump();
}
add_action( 'visidea_export_interactions_job', 'visidea_export_interactions_job' );

function visidea_recommendation_shortcode($atts) {
	// Shortcode attributes with default values
	// Example:
	// [visidea_recommendation algo="user"]
	$atts = shortcode_atts(
		array(
			'algo' => '',
		),
		$atts,
		'visidea_recommendation'
	);

	// Get the current user ID (returns 0 if not logged in)
	$user_id = get_current_user_id();

	// Get the current product ID (only if on a single product page)
	$product_id = '';
	if (is_product()) {
		global $product;
		$product_id = $product->get_id();
	}

	// Return the HTML output
	return '<div class="visidea-recommendation" algo="' . esc_attr($atts['algo']) . '" user_id="' . esc_attr($user_id) . '" item_id="' . esc_attr($product_id) . '"></div>';
}
add_shortcode( 'visidea_recommendation', 'visidea_recommendation_shortcode' );

run_visidea();
