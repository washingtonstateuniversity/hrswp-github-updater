<?php
/**
 * Load required files.
 *
 * @package HRSWP_GitHub_Updater
 * @since 0.1.0
 */

namespace HRS\HrswpGitHubUpdater\inc\load;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Load lib files.
 */
require_once dirname( __DIR__ ) . '/lib/api.php';
require_once dirname( __DIR__ ) . '/lib/options.php';

/**
 * Load inc files.
 */
require_once __DIR__ . '/update.php';

/**
 * Load admin files.
 */
require_once dirname( __DIR__ ) . '/admin/plugins.php';
