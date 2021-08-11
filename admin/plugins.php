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

	// Try to get plugin details from the transient before checking the API.
	$transient_name = hrswp\plugin_meta( 'transient_base' ) . '_' . substr( $args->slug, 0, 16 ) . '_' . md5( $update_uri );
	$plugin_details = get_transient( $transient_name );

	if ( false === $plugin_details ) {
		$plugin_details = api\get_repository_details( $update_uri );

		if ( ! is_wp_error( $plugin_details ) && ! empty( $plugin_details['zipball_url'] ) ) {
			// Save results of a successful API call to a 12-hour transient.
			set_transient( $transient_name, $plugin_details, 12 * HOUR_IN_SECONDS );
		} else {
			// Save results of an error to a 1-hour transient to prevent overloading the GitHub API.
			set_transient( $transient_name, 'request-error-wait', HOUR_IN_SECONDS );
		}
	}

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

	if ( ! is_wp_error( $plugin_details ) && 'request-error-wait' !== $plugin_details ) {
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
 * Adds a "View details" link to the plugin row meta.
 *
 * This function modifies the plugin_meta variable to add a "View Details"
 * link like the one for plugins in the WP plugin repository. The link will
 * generate the modal ({@uses install_plugin_information()}) using the
 * `plugins_api` filter, which we hook into with `get_plugin_details()`.
 *
 * @since 0.2.0
 *
 * @param array  @plugin_meta The plugin's metadata.
 * @param string @plugin_file Path to the plugin file, relative to the plugins directory.
 * @return string HTML formatted meta data for the plugins table row, altered or not.
 */
function update_plugin_row_meta( $plugin_meta, $plugin_file ) {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( __( 'Sorry, you are not allowed to manage plugins for this site.' ) );
	}

	$github_plugins = api\get_github_plugins();
	$plugin_slug    = dirname( $plugin_file );

	// Return the result now if it isn't a GitHub-hosted plugin.
	if ( ! array_key_exists( $plugin_slug, $github_plugins ) ) {
		return $plugin_meta;
	}

	$plugin_meta[] = sprintf(
		'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
		esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug . '&TB_iframe=true&width=600&height=550' ) ),
		/* translators: the plugin name */
		esc_attr( sprintf( __( 'More information about %s', 'hrswp-github-updater' ), $github_plugins[ $plugin_slug ]['name'] ) ),
		esc_attr( $github_plugins[ $plugin_slug ]['name'] ),
		__( 'View details', 'hrswp-github-updater' )
	);

	return $plugin_meta;
}
add_filter( 'plugin_row_meta', __NAMESPACE__ . '\update_plugin_row_meta', 10, 2 );
