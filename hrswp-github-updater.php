<?php
/**
 * Plugin Name: HRSWP GitHub Updater
 * Version: 0.1.0
 * Description: A WSU HRS WordPress plugin to manage updates for GitHub-hosted plugins and themes.
 * Author: Adam Turner, washingtonstateuniversity
 * Author URI: https://hrs.wsu.edu/
 * Plugin URI: https://github.com/washingtonstateuniversity/hrswp-github-updater
 * Update URI: https://github.com/washingtonstateuniversity/hrswp-github-updater/releases/latest
 * Text Domain: hrswp-github-updater
 * Requires at least: 5.8
 * Tested up to: 5.8.0
 * Requires PHP: 7.0
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.1.0
 */

namespace HrswpGitHubUpdater;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Starts things up.
add_action( 'plugins_loaded', __NAMESPACE__ . '\pre_init');

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

	require dirname( __FILE__ ) . '/lib/load.php';
}
