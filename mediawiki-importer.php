<?php
/*
Plugin Name: Mediawiki Importer
Plugin URI:
Description: Import Mediawiki content to WordPress.
Author: Prasath Nadarajah
Author URI:
Version: 0.1
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

if ( !defined('WP_LOAD_IMPORTERS') )
	return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if ( !class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require_once $class_wp_importer;
}

class Mediawiki_Import {

}

$mediawiki_import = new Mediawiki_Import();