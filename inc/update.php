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
	$managed_plugins = get_option( hrswp\plugin_meta( 'option_plugins' ) );
	$slug            = dirname( $plugin_file );

	// Return the result now if it isn't a GitHub-hosted plugin.
	if ( ! array_key_exists( $slug, $managed_plugins ) ) {
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

/**
 * Renames the update directory to match the existing directory.
 *
 * When WordPress installs a plugin update from GitHub it uses the zip file
 * name as the resulting directory name. Usually this means it installs the
 * update into a directory named for the repository, which is not always
 * going to be correct.
 *
 * @since 0.3.0
 *
 * @param string      $source        File source location.
 * @param string      $remote_source Remote file source location.
 * @param WP_Upgrader $upgrader      WP_Upgrader instance.
 * @return string|WP_Error The file source location or a WP_Error instance on failure.
 */
function rename_package_destination( $source, $remote_source, $upgrader ) {
	global $wp_filesystem;

	// Check that some things exist first.
	if ( ! isset( $source, $remote_source, $upgrader, $upgrader->skin, $wp_filesystem ) ) {
		return $source;
	}

	$dirname = api\upgrading_plugin_dirname( $upgrader );

	if ( ! $dirname ) {
		return $source;
	}

	$new_source = trailingslashit( $remote_source ) . $dirname . '/';

	$upgrader->skin->feedback(
		sprintf(
			/* translators: 1: the original directory name, 2: the corrected directory name */
			__( 'Renaming %1$s to %2$s&#8230;', 'hrswp-github-updater' ),
			'<span class="code">' . basename( $source ) . '</span>',
			'<span class="code">' . $dirname . '</span>'
		)
	);

	if ( $source !== $new_source ) {
		if ( $wp_filesystem->move( $source, $new_source, true ) ) {
			return $new_source;
		} else {
			return new WP_Error(
				'hrswp-rename-failed',
				__( 'Unable to rename the update directory.', 'hrswp-github-updater' )
			);
		}
	}

	return $source;
}

add_filter( 'update_plugins_api.github.com', __NAMESPACE__ . '\version_check', 10, 3 );
add_filter( 'upgrader_source_selection', __NAMESPACE__ . '\rename_package_destination', 10, 3 );
