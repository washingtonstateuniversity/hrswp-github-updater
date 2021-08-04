<?php
/**
 * Manage plugin options.
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.1.0
 */

namespace HRS\HrswpGitHubUpdater\lib\options;

use HRS\HrswpGitHubUpdater as hrswp;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Updates the plugin option with given values or creates it.
 *
 * @since 0.1.0
 *
 * @param array $option An array of the plugin option keys and values to update.
 * @return bool True on successful update, false on failure.
 */
function update_plugin_option( $option = array() ) {
	if ( empty( $option ) ) {
		return false;
	}

	$plugin_option = get_option( hrswp\plugin_meta( 'option_name' ) );

	/* If plugin option is missing then create it using initial values. */
	if ( ! $plugin_option ) {
		return add_option(
			hrswp\plugin_meta( 'option_name' ),
			array(
				'status'  => 'active',
				'version' => '0.0.0',
			)
		);
	}

	$plugin_option = wp_parse_args( $option, $plugin_option );

	return update_option( hrswp\plugin_meta( 'option_name' ), $plugin_option );
}

/**
 * Deletes one or more of the plugin options.
 *
 * @since 0.1.0
 *
 * @param string $option_name The key value of a plugin option value to delete.
 * @return bool True if the option was deleted, false otherwise.
 */
function delete_plugin_option( $option_name = '' ) {
	// Delete the full option value if no option name is specified.
	if ( ! $option_name ) {
		return delete_option( hrswp\plugin_meta( 'option_name' ) );
	}

	// If supplied an option name, remove that value.
	$plugin_option = get_option( hrswp\plugin_meta( 'option_name' ) );
	unset( $plugin_option[ $option_name ] );

	return update_option( hrswp\plugin_meta( 'option_name' ), $plugin_option );
}

/**
 * Updates the plugin status and version number as needed.
 *
 * @since 0.1.0
 */
function update_plugin_meta() {
	// Exit early if transient still exists or missing data function.
	if ( false !== get_transient( hrswp\plugin_meta( 'transient_name' ) ) || ! function_exists( 'get_plugin_data' ) ) {
		return;
	}

	// Update the plugin version number.
	$plugin_data = get_plugin_data( hrswp\plugin_meta( 'path' ) );
	update_plugin_option( array( 'version' => $plugin_data['Version'] ) );

	// Set the updater timeout transient to prevent checking for 12 hours.
	set_transient( hrswp\plugin_meta( 'transient_name' ), '1', 12 * HOUR_IN_SECONDS );
}
add_action( 'admin_init', __NAMESPACE__ . '\update_plugin_meta' );
