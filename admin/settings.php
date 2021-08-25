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
 * Handles query parameter actions on the plugin settings page.
 *
 * @since 0.3.0
 *
 * @return void|false Void on successful action and false otherwise.
 */
function handle_settings_actions() {
	if ( empty( $_REQUEST ) || ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	if ( isset( $_REQUEST['hrswp_gu_action'] ) && ! empty( $_REQUEST['hrswp_gu_action'] ) ) {

		if ( 'flush' === $_REQUEST['hrswp_gu_action'] ) {

			// Check the nonce.
			if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'hrswp_gu_action_flush' ) ) {
				wp_die( esc_html__( 'You do not have permission to do this thing.', 'wsuwp-a11y-status' ) );
			}

			if ( wp_installing() || wp_doing_cron() ) {
				return false;
			}

			// Flush transients.
			options\flush_transients();

			// Force refresh of plugin update information.
			wp_clean_plugins_cache( true );

			$status = 'success';
		} else {
			$status = 'fail';
		}

		$redirect = add_query_arg(
			array(
				'hrswp_gu_refresh' => $status,
				'_wpnonce'         => wp_create_nonce( 'hrswp_gu_refresh_nonce' ),
			),
			admin_url( 'options-general.php?page=hrswp-github-updater' )
		);

		wp_redirect( $redirect );
		exit;
	}

	return false;
}

/**
 * Displays a notice following a plugin settings page action.
 *
 * @since 0.3.0
 */
function settings_actions_notice() {
	if ( empty( $_REQUEST ) ) {
		return;
	}

	if ( ! isset( $_REQUEST['hrswp_gu_refresh'] ) || empty( $_REQUEST['hrswp_gu_refresh'] ) ) {
		return;
	}

	if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'hrswp_gu_refresh_nonce' ) ) {
		wp_die( esc_html__( 'You do not have permission.' ) );
	}

	$message = ( 'success' === $_REQUEST['hrswp_gu_refresh'] ) ?
		array(
			'class' => 'notice-success',
			'text'  => __( 'GitHub Updater data refreshed.' ),
		) :
		array(
			'class' => 'notice-error',
			'text'  => __( 'GitHub Updater data could not be refreshed.' ),
		);

	printf(
		'<div class="hrswp-gu notice is-dismissible %s"><p>%s</p></div>',
		esc_attr( $message['class'] ),
		esc_html( $message['text'] )
	);
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

	handle_settings_actions();

	ob_start();

	$slug = hrswp\plugin_meta( 'slug' );
	settings_fields( $slug );
	do_settings_sections( $slug );
	submit_button();
	$fields = ob_get_contents();

	ob_end_clean();

	$transient_flush_uri = wp_nonce_url(
		add_query_arg( 'hrswp_gu_action', 'flush', 'options-general.php?page=hrswp-github-updater' ),
		'hrswp_gu_action_flush'
	);

	$output  = '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
	$output .= "<form action=\"options.php\" method=\"post\">${fields}</form>";
	$output .= '<a href="' . esc_url( $transient_flush_uri ) . '">' . esc_html__( 'Refresh plugin data', 'hrswp-github-updater' ) . '</a>.';

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
add_action( 'admin_notices', __NAMESPACE__ . '\settings_actions_notice' );
