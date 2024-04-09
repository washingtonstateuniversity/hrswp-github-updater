<?php
/**
 * Manages the plugin settings screen.
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.2.0
 */

namespace HRS\HrswpGitHubUpdater\admin\settings;

use HRS\HrswpGitHubUpdater as hrswp;
use HRS\HrswpGitHubUpdater\lib\api;
use HRS\HrswpGitHubUpdater\lib\options;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Displays content on the plugin settings form before the fields.
 *
 * @since 0.2.0
 */
function settings_section_github_plugins() {
	printf(
		/* translators: 1: Documentation link, 2: sample GitHub update URL. */
		'<p>' . esc_html__( 'The GitHub Updater plugin can help to watch for and handle updates for plugins hosted on GitHub instead of the WordPress plugin directory. In order to manage a GitHub-hosted plugin it must have the %1$s with a valid GitHub API URI. A valid GitHub URI is formatted as: %2$s', 'hrswp-github-updater' ) . '</p><p>' . esc_html__( 'Save changes to refresh the version data for managed plugins.', 'hrswp-github-updater' ) . '</p>',
		'<a href="' . esc_url( 'https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/' ) . '">' . esc_html__( 'Update URI header field', 'hrswp-github-updater' ) . '</a>',
		'<code>https://api.github.com/repos/{owner}/{repo}/releases/latest</code>'
	);
}

/**
 * Displays the form fields for the plugin settings.
 *
 * @since 0.2.0
 */
function settings_field_github_plugins() {
	$github_plugins  = api\get_github_plugins();
	$managed_plugins = get_option( hrswp\plugin_meta( 'option_plugins' ) );

	if ( false === $managed_plugins ) {
		return;
	}

	$fields = '';
	foreach ( $github_plugins as $plugin_slug => $plugin_data ) {
		$checked = isset( $managed_plugins[ $plugin_slug ] ) ? $managed_plugins[ $plugin_slug ] : false;

		$fields .= sprintf(
			'<label for="%1$s"><input id="%1$s" type="checkbox" name="%2$s" value="1" %3$s /> %4$s</label><br>',
			esc_attr( $plugin_slug ),
			esc_attr( "hrswp_gu_settings[${plugin_slug}]" ),
			checked( '1', $checked, false ),
			esc_html( $plugin_data['name'] )
		);
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<fieldset><legend class="screen-reader-text"><span>' . __( 'Plugins to manage', 'hrswp-github-updater' ) . '</span></legend>' . $fields . '</fieldset>';
}

/**
 * Sanitizes the plugin settings option.
 *
 * @since 1.0.0
 *
 * @param array $option The plugin settings option to sanitize.
 * @return array The sanitized plugin settings option.
 */
function sanitize_setting( $option ) {
	if ( ! is_array( $option ) ) {
		$option = array();
	}

	foreach ( $option as $slug => $opt ) {
		if ( '1' !== (string) $opt ) {
			$option[ $slug ] = '1';
		}
	}

	return $option;
}

/**
 * Displays the GitHub Updater settings page for managing options.
 *
 * @since 0.2.0
 */
function settings_page_content() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	/*
	 * Refresh plugin update information when settings are saved. WP doesn't
	 * pass the nonce all the way through to here, so we don't have it.
	 */
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
		if ( ! wp_installing() && ! wp_doing_cron() ) {
			options\flush_transients();
			wp_clean_plugins_cache( true );
		}
	}

	ob_start();

	$slug = hrswp\plugin_meta( 'slug' );
	settings_fields( $slug );
	do_settings_sections( $slug );
	submit_button();
	$fields = ob_get_contents();

	ob_end_clean();

	$output  = '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
	$output .= "<form action=\"options.php\" method=\"post\">${fields}</form>";

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo "<div class=\"wrap\">${output}</div>";
}

/**
 * Displays a notice if no plugins are being managed.
 *
 * @since 0.3.0
 */
function unmanaged_plugins_nag() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// If there are managed plugins, don't bother.
	if ( ! empty( get_option( hrswp\plugin_meta( 'option_plugins' ) ) ) ) {
		return;
	}

	// Check the plugin status option for unmanaged plugins nag ignore.
	if ( 'show' !== options\get_plugin_option( 'unmanaged_plugins_nag' ) ) {
		return;
	}

	// Display the notice if no plugins are being managed.
	printf(
		'<div class="hrswp-gu notice notice-warning"><p>%1$s</p><p>%2$s | %3$s</p></div>',
		'<strong>' . esc_html__( 'Notice: ', 'hrswp-github-updater' ) . '</strong>' . esc_html__( 'The GitHub Updater plugin is not currently watching any plugins for updates. Do you want to select the plugins you want to manage?', 'hrswp-github-updater' ),
		'<a href="' . esc_url( get_admin_url( get_current_blog_id(), 'options-general.php?page=hrswp-github-updater' ) ) . '">' . esc_html__( 'Yes, update settings', 'hrswp-github-updater' ) . '</a>',
		'<a href="' . esc_url(
			add_query_arg(
				array(
					'hrswp_gu_unmanaged_plugins_nag' => 0,
					'_wpnonce'                       => wp_create_nonce( 'hrswp_gu_unmanaged_plugins_nag_nonce' ),
				)
			)
		) . '">' . esc_html__( 'No thanks, do not remind me again', 'hrswp-github-updater' ) . '</a>'
	);
}

/**
 * Manages display of the unmanaged GitHub plugins notice.
 *
 * @since 0.3.0
 */
function unmanaged_plugins_nag_handler() {
	// Early exit.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['hrswp_gu_unmanaged_plugins_nag'] ) && (string) '0' === $_GET['hrswp_gu_unmanaged_plugins_nag'] ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'hrswp_gu_unmanaged_plugins_nag_nonce' ) ) {
			wp_die( esc_html__( 'The link has expired.', 'hrswp-github-updater' ) );
		}

		// Update the plugin option.
		options\update_plugin_option( array( 'unmanaged_plugins_nag' => 'hide' ) );

		// Remove the URL query strings.
		if ( wp_safe_redirect( esc_url( remove_query_arg( array( 'hrswp_gu_unmanaged_plugins_nag', '_wpnonce' ) ) ) ) ) {
			exit;
		}
	}
}

/**
 * Registers plugin settings and settings form fields.
 *
 * @since 0.2.0
 */
function register_settings() {
	$slug   = hrswp\plugin_meta( 'slug' );
	$option = hrswp\plugin_meta( 'option_plugins' );

	// Register setting to store which GitHub plugins to manage.
	register_setting(
		$slug,
		$option,
		array(
			'sanitize_callback' => __NAMESPACE__ . '\sanitize_setting',
		)
	);

	add_settings_section(
		$slug . '_section_github_plugins',
		__( 'GitHub-hosted Plugins', 'hrswp-github-updater' ),
		__NAMESPACE__ . '\settings_section_github_plugins',
		$slug
	);

	add_settings_field(
		$option,
		__( 'Plugins to manage', 'hrswp-github-updater' ),
		__NAMESPACE__ . '\settings_field_github_plugins',
		$slug,
		$slug . '_section_github_plugins'
	);
}

/**
 * Registers the plugin settings page.
 *
 * @since 0.2.0
 */
function register_settings_page() {
	add_options_page(
		__( 'GitHub Updater Settings', 'hrswp-github-updater' ),
		__( 'GitHub Updater', 'hrswp-github-updater' ),
		'manage_options',
		hrswp\plugin_meta( 'slug' ),
		__NAMESPACE__ . '\settings_page_content'
	);
}

add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );
add_action( 'admin_init', __NAMESPACE__ . '\unmanaged_plugins_nag_handler' );
add_action( 'admin_menu', __NAMESPACE__ . '\register_settings_page' );
add_action( 'admin_notices', __NAMESPACE__ . '\unmanaged_plugins_nag' );
