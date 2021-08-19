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
		/* translators: %s: Documentation link. */
		'<p>' . esc_html__( 'The GitHub Updater plugin can help to watch for and handle updates for plugins hosted on GitHub instead of the WordPress plugin directory. In order to manage a GitHub-hosted plugin it must have the %s with a valid GitHub API URI. A valid GitHub URI is formatted as: https://api.github.com/repos/{owner}/{repo}/releases/latest', 'hrswp-github-updater' ) . '</p>',
		'<a href="' . esc_url( 'https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/' ) . '">' . esc_html__( 'Update URI header field', 'hrswp-github-updater' ) . '</a>'
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
 * Displays the GitHub Updater settings page for managing options.
 *
 * @since 0.2.0
 */
function settings_page_content() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
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
 * Registers plugin settings and settings form fields.
 *
 * @since 0.2.0
 */
function register_settings() {
	$slug   = hrswp\plugin_meta( 'slug' );
	$option = hrswp\plugin_meta( 'option_plugins' );

	// Register setting to store which GitHub plugins to manage.
	register_setting( $slug, $option );

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
add_action( 'admin_menu', __NAMESPACE__ . '\register_settings_page' );
