<?php
/**
 * Plugin Name: HRSWP GitHub Updater
 * Version: 0.2.0-rc.1
 * Description: A WSU HRS WordPress plugin to manage updates for GitHub-hosted plugins and themes.
 * Author: Adam Turner, washingtonstateuniversity
 * Author URI: https://hrs.wsu.edu/
 * Plugin URI: https://github.com/washingtonstateuniversity/hrswp-github-updater
 * Update URI: https://api.github.com/repos/washingtonstateuniversity/hrswp-github-updater/releases/latest
 * Text Domain: hrswp-github-updater
 * Requires at least: 5.8
 * Tested up to: 5.8.0
 * Requires PHP: 7.3
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.1.0
 */

namespace HRS\HrswpGitHubUpdater;

use HRS\HrswpGitHubUpdater\lib\options;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Starts things up.
add_action( 'plugins_loaded', __NAMESPACE__ . '\pre_init' );

/* Register lifecycle methods. */
register_activation_hook( __FILE__, __NAMESPACE__ . '\activate' );
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate' );
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\uninstall' );

/**
 * Displays a version notice.
 *
 * @since 0.1.0
 */
function wordpress_version_notice() {
	printf(
		'<div class="error"><p>%s</p></div>',
		esc_html__( 'The HRSWP GitHub Updater requires WordPress 5.8.0 or later to function properly. Please upgrade WordPress before activating.', 'hrswp-github-updater' )
	);
}

/**
 * Verifies the plugin dependencies are present, then loads it.
 *
 * @since 0.1.0
 */
function pre_init() {
	global $wp_version;

	// Get unmodified $wp_version.
	include ABSPATH . WPINC . '/version.php';

	// Remove '-src' from the version string for `version_compare()`.
	$version = preg_replace( '/-[A-Za-z-0-9]*$/', '', $wp_version );

	if ( version_compare( $version, '5.8', '<' ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\wordpress_version_notice' );
		deactivate_plugins( array( 'hrswp-github-updater/hrswp-github-updater.php' ) );
		return;
	}

	/* Load required plugin files. */
	require dirname( __FILE__ ) . '/inc/load.php';
}

/**
 * Manages plugin metadata for ease of access.
 *
 * @since 0.1.0
 *
 * @param string $meta Optional. A specific plugin metadata key to return.
 * @return string|array The requested metadata value or an array of all plugin metadata.
 */
function plugin_meta( $meta = '' ) {
	$plugin_meta = array(
		'path'           => __FILE__,
		'slug'           => 'hrswp-github-updater',
		'option_status'  => 'hrswp_gu_status',
		'option_plugins' => 'hrswp_gu_settings',
		'transient_base' => 'hrswp_gu',
	);

	if ( '' !== $meta ) {
		return $plugin_meta[ (string) $meta ];
	}

	return $plugin_meta;
}

/**
 * Activates the plugin.
 *
 * @since 0.1.0
 */
function activate() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	if ( ! function_exists( __NAMESPACE__ . '\lib\options\update_plugin_option' ) ) {
		require dirname( __FILE__ ) . '/lib/options.php';
	}

	options\update_plugin_option( array( 'status' => 'active' ) );
}

/**
 * Deactivates the plugin.
 *
 * @since 0.1.0
 */
function deactivate() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	if ( ! function_exists( __NAMESPACE__ . '\lib\options\update_plugin_option' ) ) {
		require dirname( __FILE__ ) . '/lib/options.php';
	}

	options\update_plugin_option( array( 'status' => 'inactive' ) );
}

/**
 * Uninstalls the plugin.
 *
 * @since 0.1.0
 */
function uninstall() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	if ( ! function_exists( __NAMESPACE__ . '\lib\options\update_plugin_option' ) ) {
		require dirname( __FILE__ ) . '/lib/options.php';
	}

	// Unregister plugin settings.
	unregister_setting( plugin_meta( 'slug' ), plugin_meta( 'option_plugins' ) );

	// Remove plugin options.
	options\delete_plugin_option();
	delete_option( plugin_meta( 'option_plugins' ) );

	// Remove plugin transients.
	options\flush_transients();
}
