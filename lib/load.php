<?php
/**
 * Load required files.
 *
 * @package
 * @since 0.1.0
 */

namespace HRS\HrswpGitHubUpdater\lib\load;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Silence is golden.' );
}

/**
 * Load lib files.
 */
require dirname( __FILE__ ) . '/options.php';

/**
 * Load inc files.
 */



// This will need to go somewhere that isn't here.
// add_filter( 'update_plugins_github.com', array( $this, 'update' ), 10, 4 );

/**
 *
 * Here's what this returns:
 *
 * $update      = 	boolean false
 * $plugin_data = 	array (size=14)
 * 				    'Name' => string 'HRSWP Blocks' (length=12)
 * 					'PluginURI' => string 'https://github.com/washingtonstateuniversity/hrswp-plugin-blocks' (length=64)
 *					'Version' => string '1.0.4' (length=5)
	* 					'Description' => string 'A WSU HRS WordPress plugin to provide custom blocks and WP block editor adjustments.' (length=84)
	* 					'Author' => string 'Adam Turner, washingtonstateuniversity' (length=38)
	* 					'AuthorURI' => string 'https://hrs.wsu.edu/' (length=20)
	* 					'TextDomain' => string 'hrswp-blocks' (length=12)
	* 					'DomainPath' => string '' (length=0)
	* 					'Network' => boolean false
	* 					'RequiresWP' => string '5.7' (length=3)
	* 					'RequiresPHP' => string '7.0' (length=3)
	* 					'UpdateURI' => string 'https://github.com/washingtonstateuniversity/hrswp-plugin-blocks/releases/latest' (length=80)
	* 					'Title' => string 'HRSWP Blocks' (length=12)
	* 					'AuthorName' => string 'Adam Turner, washingtonstateuniversity' (length=38)
	* $plugin_file = 	string 'hrswp-plugin-blocks/hrswp-blocks.php' (length=36)
	* $locales     =	array (size=0) empty
	*/
/*
public function update( $update, $plugin_data, $plugin_file, $locales ) {
	if ( $plugin_file !== basename( dirname( self::$basename ) ) . '/' . basename( self::$basename ) ) {
		return false;
	}

	var_dump( $plugin_data['Version'] );
	wp_die( 'Testing' );

	return $update;
}
*/
