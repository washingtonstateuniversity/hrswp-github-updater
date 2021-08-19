<?php
/**
 * Functions to handle checking and handling updates.
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.2.0
 */

namespace HRS\HrswpGitHubUpdater\inc\update;

use HRS\HrswpGitHubUpdater as hrswp;
use HRS\HrswpGitHubUpdater\lib\api;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Checks for available updates to GitHub-hosted plugins.
 *
 * Callback function for the `update_plugins_{$hostname}` filter in the
 * `wp_update_plugins()` function, found in `wp-includes/update.php`.
 * Retrieves the most up-to-date plugin information from the GitHub API
 * and returns it for use in the WordPress function.
 *
 * @since 0.2.0
 *
 * @param array|false $update      The plugin update data with the latest details. Default false.
 * @param array       $plugin_data Plugin headers.
 * @param string      $plugin_file Plugin filename.
 * @return object|false The plugin update data with the latest details or false.
 */
function version_check( $update, $plugin_data, $plugin_file ) {
	$github_plugins = api\get_github_plugins();
	$slug           = dirname( $plugin_file );

	// Return the result now if it isn't a GitHub-hosted plugin.
	if ( ! array_key_exists( $slug, $github_plugins ) ) {
		return $update;
	}

	$repository_details = api\get_repository_details( $plugin_data['UpdateURI'], $slug );

	if ( ! is_wp_error( $repository_details ) && is_array( $repository_details ) ) {
		$update               = (object) $update;
		$update->slug         = $slug;
		$update->version      = str_replace( 'v', '', $repository_details['tag_name'] );
		$update->url          = $plugin_data['PluginURI'];
		$update->package      = $repository_details['zipball_url'];
		$update->tested       = $plugin_data['Tested up to'];
		$update->requires_php = $plugin_data['RequiresPHP'];
	}

	return $update;
}
add_filter( 'update_plugins_api.github.com', __NAMESPACE__ . '\version_check', 10, 3 );
