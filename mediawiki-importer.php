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

if ( class_exists( 'WP_Importer' ) ) {
class Mediawiki_Import {

	function header() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>'.__('Import Mediawiki', 'mediawiki-importer').'</h2>';
	}

	function footer() {
		echo '</div>';
	}

	function display_menu() {
		?>
			<p>
				<a href="?import=mediawiki&step=2"><?php _e( 'Import Page by title' , 'mediawiki-importer') ?></a>
			</p>
		<?php
	}

	function get_page_by_title() {

	}

	function greet() {
		?>
			<div class="narrow">
				<form action="admin.php?import=mediawiki&step=1" method="post">
					<p><?php _e( 'Howdy! This importer allows you to connect to mediawiki based sites and import content' , 'mediawiki-importer') ?></p>
					<p><?php _e( 'Enter your Mediawiki username and password below:' , 'mediawiki-importer') ?></p>

					<table class="form-table">

						<tr>
							<th scope="row"><label for="mw_username"><?php _e( 'Mediawiki Username' , 'mediawiki-importer') ?></label></th>
							<td><input type="text" name="mw_username" id="mw_username" class="regular-text" /></td>
						</tr>

						<tr>
							<th scope="row"><label for="mw_password"><?php _e( 'Mediawiki Password' , 'mediawiki-importer') ?></label></th>
							<td><input type="password" name="mw_password" id="mw_password" class="regular-text" /></td>
						</tr>

					</table>

					<p class="submit">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Connect to Mediawiki site and Import' , 'mediawiki-importer') ?>" />
					</p>

				</form>
			</div>
		<?php
	}

	function dispatch() {
		if ( empty ( $_GET['step'] ) )
			$step = 0;
		else
			$step = (int) $_GET['step'];

		$this->header();

		switch ($step) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				$this->display_menu();
				break;
		}

		$this->footer();
	}

	function Mediawiki_Import() {
		// Nothing.
	}
}

$mediawiki_import = new Mediawiki_Import();

register_importer( 'mediawiki', __( 'Mediawiki', 'mediawiki-importer' ), __( 'Import content from Mediawiki sites.', 'mediawiki-importer' ), array( $mediawiki_import, 'dispatch' ) );

}