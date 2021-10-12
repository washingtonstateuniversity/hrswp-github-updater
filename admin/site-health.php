<?php
/**
 * Manages tests shown on the site health screen.
 *
 * @package HRSWP_GitHub_Updater
 * @since 1.0.0
 */

namespace HRS\HrswpGitHubUpdater\admin\SiteHealth;

use HRS\HrswpGitHubUpdater as hrswp;
use HRS\HrswpGitHubUpdater\lib\api;
use HRS\HrswpGitHubUpdater\lib\options;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Tests if the site can communicate with the provided GitHub update URIs.
 *
 * @since 1.0.0
 *
 * @return array The test results.
 */
function get_test_hrswpgu_github_uri() {
	// Set the default status.
	$result = array(
		'label'       => __( 'Can communicate with all managed GitHub plugin Update URIs', 'hrswp-github-updater' ),
		'status'      => '',
		'badge'       => array(
			'label' => __( 'Security', 'hrswp-github-updater' ),
			'color' => 'blue',
		),
		'description' => '<p>' . __( 'Communicating with the GitHub API for each managed GitHub plugin is used to check for new versions and install updates. The URI is provided by the plugin in the Update URI plugin header.', 'hrswp-github-updater' ) . '</p>',
		'test'        => 'hrswpgu_github_uri',
	);

	$managed_plugins = get_option( hrswp\plugin_meta( 'option_plugins' ) );

	// Return early if there are no managed plugins.
	if ( false === $managed_plugins ) {
		return $result;
	}

	$github_plugins  = api\get_github_plugins();
	$success_message = '';
	$error           = false;

	// Do the API calls for managed plugins only.
	foreach ( $managed_plugins as $slug => $value ) {
		list(
			'name'         => $name,
			'update_uri'   => $update_uri,
		) = $github_plugins[ $slug ];

		// Try to get plugin details from the transient before checking the API.
		$transient = hrswp\plugin_meta( 'transient_base' ) . '_' . substr( $slug, 0, 16 ) . '_' . md5( $update_uri );
		$response  = get_transient( $transient );

		if ( isset( $response['etag'] ) || false === $response ) {
			if ( isset( $response['etag'] ) ) {
				$args = array(
					'timeout' => 10,
					'headers' => array( 'If-None-Match' => $response['etag'] ),
				);
			} else {
				$args = array( 'timeout' => 10 );
			}

			$response = wp_remote_get( esc_url_raw( $update_uri ), $args );
			$code     = wp_remote_retrieve_response_code( $response );
		}

		// If we've already gotten an error this hour, then just return that.
		if ( isset( $response['error_code'] ) ) {
			$code          = $response['error_code'];
			$error_message = $response['error_message'];
		}

		if ( ! in_array( $code, array( 200, 302, 304 ), true ) ) {
			$error         = true;
			$error_message = ( ! isset( $error_message ) ) ? $response->get_error_message() : $error_message;

			$result['description'] .= sprintf(
				'<p>%s</p>',
				sprintf(
					'<span class="dashicons error"><span class="screen-reader-text">%s</span></span> %s<span class="hrswp-gu-error-details">%s</span>',
					__( 'Error', 'hrswp-github-updater' ),
					sprintf(
						/* translators: 1: The plugin name. 2: The error code. */
						__( '%1$s: Unable to reach GitHub, received the error code %2$s.', 'hrswp-github-updater' ),
						esc_html( $name ),
						esc_html( $code )
					),
					sprintf(
						/* translators: 1: The GitHub URI provided in the plugin header. 2: The error returned by the lookup. */
						__( 'Tried to reach GitHub at %1$s and got the error: %2$s', 'hrswp-github-updater' ),
						'<code>' . esc_url( $update_uri ) . '</code>',
						esc_html( $error_message )
					)
				)
			);
		} else {
			$success_message .= sprintf(
				'<li><span class="dashicons good"></span>%s</li>',
				sprintf(
					/* translators: 1: The plugin name. 2: The HTTP response code. */
					__( '%1$s (HTTP code %2$s)', 'hrswp-github-updater' ),
					esc_html( $name ),
					esc_html( $code )
				)
			);
		}
	}

	if ( '' !== $success_message ) {
		$result['description'] .= '<h5>' . __( 'Successful API calls', 'hrswp-github-updater' ) . '</h5><ul>' . $success_message . '</ul>';
	}

	if ( true !== $error ) {
		$result['status'] = 'good';
	} else {
		$result['status'] = 'critical';
		$result['label']  = __( 'Could not reach one or more managed GitHub plugin Update URIs', 'hrswp-github-updater' );
	}

	return $result;
}

/**
 * Adds the GitHub URI tests to the WordPress site status tests.
 *
 * @since 1.0.0
 *
 * @param array[] $tests An associative array of direct and asynchronous tests.
 * @return array[] The modified associative array of direct and asynchronous tests.
 */
function site_status_tests( $tests ) {
	$tests['async']['hrswpgu_github_uri'] = array(
		'label'             => __( 'GitHub Update URI Tests', 'hrswp-github-updater' ),
		'test'              => rest_url( hrswp\plugin_meta( 'slug' ) . '/v1/test/github-uri-communication' ),
		'has_rest'          => true,
		'async_direct_test' => __NAMESPACE__ . '\get_test_hrswpgu_github_uri',
	);

	return $tests;
}

/**
 * Adds the HRSWP GitHub Updater debug info to the site health screen.
 *
 * @since 1.0.0
 *
 * @param array $info The debug information to be added to the core information page.
 * @return array The modified debug information to display on the core information page.
 */
function site_health_debug_info( $info ) {
	$github_plugins  = api\get_github_plugins();
	$managed_plugins = get_option( hrswp\plugin_meta( 'option_plugins' ), array() );
	$fields          = array();

	foreach ( $github_plugins as $slug => $plugin_data ) {
		$fields[ $slug ] = array(
			'label' => $plugin_data['name'],
			'value' => array(
				'Update URI' => $plugin_data['update_uri'],
				'Managed'    => ( ! array_key_exists( $slug, $managed_plugins ) )
					? __( 'No', 'hrswp-github-updater' )
					: __( 'Yes', 'hrswp-github-updater' ),
			),
		);
	}

	$info[ hrswp\plugin_meta( 'slug' ) ] = array(
		'label'       => __( 'HRSWP Github Updater', 'hrswp-github-updater' ),
		'description' => __( 'Shows all of the plugins with a GitHub Update URI and whether they are managed by the HRSWP GitHub Updater plugin.', 'hrswp-github-updater' ),
		'fields'      => $fields,
	);

	return $info;
}

/**
 * Enqueues custom CSS on the site health screen.
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix The hook suffix for the current page.
 */
function site_health_enqueue_scripts( $hook_suffix ) {
	if ( 'site-health.php' !== $hook_suffix ) {
		return;
	}
	wp_enqueue_style(
		hrswp\plugin_meta( 'slug' ) . '-site-health',
		plugins_url( 'css/site-health.min.css', hrswp\plugin_meta( 'path' ) ),
		array(),
		options\get_plugin_option( 'version' )
	);
}

add_filter( 'site_status_tests', __NAMESPACE__ . '\site_status_tests', 10, 1 );
add_filter( 'debug_information', __NAMESPACE__ . '\site_health_debug_info', 10, 1 );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\site_health_enqueue_scripts' );
