<?php
/**
 * Modifications to the WP Plugins administration panel.
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.2.0
 */

namespace HRS\HrswpGitHubUpdater\admin\plugins;

use HRS\HrswpGitHubUpdater as hrswp;
use HRS\HrswpGitHubUpdater\lib\api;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Adds custom headers for WordPress and PHP version requirements.
 *
 * Callback function for the `extra_{$context}_headers` WP Filter hook,
 * where here `$context` is `plugin`. This hook fires in `get_file_data()`,
 * called in this context by `get_plugin_data()`. The `get_plugin_data`
 * function passes the `plugin` context, allowing us to add some custom
 * headers to the plugin data parser. The values passed here must match
 * the head matter in the main plugin file, and must be passed as-is to the
 * `WSUWP_Help_Docs_Updater->display_plugin_details()` results object.
 *
 * @link https://developer.wordpress.org/reference/functions/get_file_data/
 * @link https://developer.wordpress.org/reference/functions/get_plugin_data/
 * @link https://codex.wordpress.org/File_Header
 *
 * @since 0.4.1
 *
 * @param array $extra_headers List of headers, in the format array('HeaderKey' => 'Header Name').
 * @return array Array of file headers to add to the default headers array.
 */
function add_plugin_headers( $extra_headers ) {
	$extra_headers = array(
		'TestedUpTo' => 'Tested up to',
	);

	return $extra_headers;
}
add_filter( 'extra_plugin_headers', __NAMESPACE__ . '\add_plugin_headers', 10, 1 );

/**
 * Displays the plugin details modal.
 *
 * The callback function for the 'plugins_api' WP API filter hook. This
 * retrieves and displays the plugin release info along with the plugin
 * meta information in the WordPress details modal window.
 *
 * @since 0.2.0
 *
 * @param object $result Required. The result object is required for the `plugin_information` action.
 * @param string $action The type of information being requested from the Plugin Installation API.
 * @param object $args The Plugin API arguments.
 * @return object|false The result object with the plugin details added or false.
 */
function get_plugin_details( $result, $action, $args ) {
	// Do nothing if this isn't a request for information.
	if ( 'plugin_information' !== $action ) {
		return false;
	}

	$github_plugins = api\get_github_plugins();

	// Return the result now if it isn't a GitHub-hosted plugin.
	if ( ! array_key_exists( $args->slug, $github_plugins ) ) {
		return $result;
	}

	list(
		'name'         => $name,
		'description'  => $description,
		'author'       => $author,
		'author_uri'   => $author_uri,
		'plugin_uri'   => $plugin_uri,
		'requires_wp'  => $requires_wp,
		'requires_php' => $requires_php,
		'tested'       => $tested,
		'update_uri'   => $update_uri,
	) = $github_plugins[ $args->slug ];

	$result                    = new \stdClass();
	$result->name              = $name;
	$result->slug              = $args->slug;
	$result->requires          = $requires_wp;
	$result->tested            = $tested;
	$result->requires_php      = $requires_php;
	$result->author            = $author;
	$result->author_profile    = $author_uri;
	$result->homepage          = $plugin_uri;
	$result->short_description = $description;
	$result->sections          = array( 'description' => $description );

	$managed_plugins = get_option( hrswp\plugin_meta( 'option_plugins' ) );

	// Return the result now if it isn't a GitHub-hosted plugin.
	if ( ! array_key_exists( $args->slug, $managed_plugins ) ) {
		$result->sections = array(
			'description' => sprintf(
				/* translators: %s: Link to the plugin options screen, %s: The plugin description content. */
				'<strong>' . __( 'Not currently managed by GitHub Updater plugin. Visit the %s to add it.', 'hrswp-github-updater' ) . '</strong><br><br>%s',
				'<a href="' . esc_url( get_admin_url( get_current_blog_id(), 'options-general.php?page=hrswp-github-updater' ) ) . '">' . __( 'plugin settings', 'hrswp-github-updater' ) . '</a>',
				$description
			),
		);
		return $result;
	}

	$plugin_details = api\get_repository_details( $update_uri, $args->slug );

	if ( ! is_wp_error( $plugin_details ) && is_array( $plugin_details ) ) {
		$changelog = sprintf(
			/* translators: 1: the plugin version number, 2: the HTML formatted release message from GitHub */
			__( '<strong>Version %1$s Changes</strong>%2$s', 'hrswp-github-updater' ),
			$plugin_details['tag_name'],
			apply_filters( 'the_content', $plugin_details['body'] )
		);

		$result->version       = str_replace( 'v', '', $plugin_details['tag_name'] );
		$result->last_updated  = $plugin_details['published_at'];
		$result->sections      = array(
			'description' => $description,
			'changelog'   => $changelog,
		);
		$result->download_link = $plugin_details['zipball_url'];
	}

	return $result;
}
add_filter( 'plugins_api', __NAMESPACE__ . '\get_plugin_details', 10, 3 );

/**
 * Adds a "Settings" action to the plugin action links.
 *
 * @since 0.2.0
 *
 * @param string[] $actions     An array of plugin action links.
 * @return string[] The modified array of plugin action links.
 */
function add_plugin_row_actions( $actions ) {
	$actions[] = '<a href="' . esc_url( get_admin_url( get_current_blog_id(), 'options-general.php?page=hrswp-github-updater' ) ) . '">' . __( 'Settings', 'hrswp-github-updater' ) . '</a>';

	return $actions;
}
add_filter( 'plugin_action_links_' . plugin_basename( hrswp\plugin_meta( 'path' ) ), __NAMESPACE__ . '\add_plugin_row_actions', 10, 1 );
