<?php
/**
 * Load required files.
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.1.0
 */

namespace HRS\HrswpGitHubUpdater\lib\load;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Load lib files.
 */
require_once __DIR__ . '/api.php';
require_once __DIR__ . '/options.php';

/**
 * Load admin files.
 */
require_once dirname( __DIR__ ) . '/admin/plugins.php';
