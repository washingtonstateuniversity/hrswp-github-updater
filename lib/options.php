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
				'status'  => 'activated',
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
