<?php
/**
 * Functions for interacting with the GitHub API.
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.2.0
 */

namespace HRS\HrswpGitHubUpdater\lib\api;

use HRS\HrswpGitHubUpdater as hrswp;
use HRS\HrswpGitHubUpdater\lib\options;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Gets a list of all plugins with a GitHub hostname in Update URI header.
 *
 * It is recommended to call this function at least after the
 * `after_setup_theme` action so that plugins and themes have the ability
 * to filter the results of `get_plugins`.
 *
 * @since 0.2.0
 *
 * @return array[] An array of GitHub-hosted plugins keyed by plugin slug.
 */
function get_github_plugins() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// Get all site plugins.
	$plugins = get_plugins();

	// Save each plugin with a GitHub hostname.
	$github_plugins = array();
	foreach ( $plugins as $plugin_file => $plugin_data ) {
		if ( ! $plugin_data['UpdateURI'] ) {
			continue;
		}

		if ( 'api.github.com' !== wp_parse_url( $plugin_data['UpdateURI'], PHP_URL_HOST ) ) {
			continue;
		}

		$github_plugins[ dirname( plugin_basename( $plugin_file ) ) ] = array(
			'name'           => $plugin_data['Name'],
			'description'    => $plugin_data['Description'],
			'author'         => $plugin_data['AuthorName'],
			'author_uri'     => $plugin_data['AuthorURI'],
			'plugin_uri'     => $plugin_data['PluginURI'],
			'plugin_version' => $plugin_data['Version'],
			'requires_wp'    => $plugin_data['RequiresWP'],
			'requires_php'   => $plugin_data['RequiresPHP'],
			'tested'         => $plugin_data['Tested up to'],
			'update_uri'     => $plugin_data['UpdateURI'],
		);
	}

	return $github_plugins;
}

/**
 * Gets the plugin repository info from the GitHub API.
 *
 * Connects to the plugin repository using the GitHub API (v3) to retrieve
 * repo data in JSON format and parse it.
 *
 * @link https://developer.github.com/v3/
 *
 * @since 0.4.0
 *
 * @param string $request_uri Required. The full URI of the GitHub repository to fetch.
 * @param string $slug        Required. The slug name of the plugin to check.
 * @return array|WP_Error|string Array of parsed JSON GitHub repository details, or a WP_Error object if the request failed and a string if it failed more than once in an hour.
 */
function get_repository_details( $request_uri = '', $slug = '' ) {
	// Try to get plugin details from the transient before checking the API.
	$transient = hrswp\plugin_meta( 'transient_base' ) . '_' . substr( $slug, 0, 16 ) . '_' . md5( $request_uri );
	$response  = get_transient( $transient );

	if ( false === $response ) {
		// Add transient key to the plugin options for tracking.
		options\update_transient_keys( $transient );

		$response = wp_remote_get( esc_url_raw( $request_uri ) );

		// Checks for WP Error, missing response, and incorrect response type.
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( '' === $response_code || ! in_array( (int) $response_code, array( 200, 302, 304 ), true ) ) {
			$error = sprintf(
				/* translators: the API request URL */
				__( 'GitHub API request failed. The request for %s returned an invalid response.', 'hrswp-github-updater' ),
				esc_url_raw( $request_uri )
			);

			// Save results of an error to a 1-hour transient to prevent overloading the GitHub API.
			set_transient( $transient, 'request-error-wait', HOUR_IN_SECONDS );

			return new \WP_Error( 'invalid-response', $error );
		}

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		// Save results of a successful API call to a 10-hour transient.
		set_transient( $transient, $response, 10 * HOUR_IN_SECONDS );
	}

	return $response;
}
