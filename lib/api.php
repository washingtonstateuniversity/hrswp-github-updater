<?php
/**
 * Functions for interacting with the GitHub API.
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.2.0
 */

namespace HRS\HrswpGitHubUpdater\lib\api;

use HRS\HrswpGitHubUpdater as hrswp;
use HRS\HrswpGitHubUpdater\admin\siteHealth;
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
		if ( true !== validate_github_uri( $plugin_data['UpdateURI'] ) ) {
			continue;
		}

		$github_plugins[ dirname( plugin_basename( $plugin_file ) ) ] = array(
			'file'           => $plugin_file,
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
 * @since 1.0.0
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
			$error = ( is_wp_error( $response ) )
				? $response->get_error_message()
				: sprintf(
					/* translators: the API request URL */
					__( 'GitHub API request failed. The request for %s returned an invalid response.', 'hrswp-github-updater' ),
					esc_url_raw( $request_uri )
				);

			$response = array(
				'error_message' => $error,
				'error_code'    => $response_code,
			);

			// Save results of an error to a 1-hour transient to prevent overloading the GitHub API.
			set_transient( $transient, $response, HOUR_IN_SECONDS );

			return new \WP_Error( 'invalid-response', $error );
		}

		$etag = isset( $response['headers']['etag'] ) ? $response['headers']['etag'] : '';

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		// Push the Etag to the response array.
		$response['etag'] = $etag;

		// Save results of a successful API call to a 10-hour transient.
		set_transient( $transient, $response, 10 * HOUR_IN_SECONDS );
	}

	return $response;
}

/**
 * Gets the dirname if the thing being upgraded is managed by GitHub Updater.
 *
 * @since 0.3.0
 *
 * @param WP_Upgrader $upgrader WP_Upgrader instance for the thing being upgraded.
 * @return string|false The directory name if the thing being upgraded is managed by this plugin, false if error or not managed.
 */
function upgrading_plugin_dirname( $upgrader ) {
	if ( ! isset( $upgrader, $upgrader->skin ) ) {
		return false;
	}

	$skin = $upgrader->skin;

	if ( isset( $skin->plugin_info ) && isset( $skin->plugin_info['UpdateURI'] ) ) {
		$github_plugins  = get_github_plugins();
		$managed_plugins = get_option( hrswp\plugin_meta( 'option_plugins' ) );

		foreach ( $github_plugins as $slug => $plugin_data ) {
			if ( $skin->plugin_info['UpdateURI'] === $plugin_data['update_uri'] && array_key_exists( $slug, $managed_plugins ) ) {
				return $slug;
			}
		}
	}

	return false;
}

/**
 * Registers the rest route for running Site Health tests.
 *
 * @since 1.0.0
 */
function register_rest_routes() {
	register_rest_route(
		hrswp\plugin_meta( 'slug' ) . '/v1',
		'/test/github-uri-communication',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'HRS\HrswpGitHubUpdater\admin\siteHealth\get_test_hrswpgu_github_uri',
				'permission_callback' => function () {
					return current_user_can( 'view_site_health_checks' );
				},
			),
		)
	);
}

/**
 * Validates GitHub update URIs.
 *
 * @since 1.0.0
 *
 * @param string $uri A URI to check.
 * @return bool True if the URI is valid, false if invalid.
 */
function validate_github_uri( $uri = '' ) {
	if ( '' === $uri ) {
		return false;
	}

	$parsed_uri = wp_parse_url( $uri );

	if ( 'api.github.com' !== $parsed_uri['host'] ) {
		return false;
	}

	if ( 'https' !== $parsed_uri['scheme'] ) {
		return false;
	}

	if ( '/repos' !== dirname( $parsed_uri['path'], 4 ) ) {
		return false;
	}

	return true;
}

add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );
