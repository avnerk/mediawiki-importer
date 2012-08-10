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

	private $site_url;
	private $cookie;

	function header() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>'.__('Import Mediawiki', 'mediawiki-importer').'</h2>';
	}

	function footer() {
		echo '</div>';
	}

	function display_menu() {

		$username = sanitize_text_field( $_POST['mw_username'] );
		$password = sanitize_text_field( $_POST['mw_password'] );
		$siteurl = sanitize_text_field( $_POST['mw_siteurl'] );

		$path = $siteurl . '/api.php?action=login&lgname=' . $username . '&lgpassword=' . $password;
		$response = wp_remote_get( $path );
		var_dump( $response );

		?>
			<p>
				<a href="?import=mediawiki&step=2"><?php _e( 'Import Page by title' , 'mediawiki-importer') ?></a>
			</p>
		<?php
	}

	function display_get_page_by_title() {
		?>
			<div class="narrow">
				<form action="admin.php?import=mediawiki&step=3" method="post">
					<p><?php _e( 'Enter page title to retrieve:' , 'mediawiki-importer') ?></p>

					<table class="form-table">

						<tr>
							<th scope="row"><label for="mw_pagetitle"><?php _e( 'Page title' , 'mediawiki-importer') ?></label></th>
							<td><input type="text" name="mw_pagetitle" id="mw_pagetitle" class="regular-text" /></td>
						</tr>

					</table>

					<p class="submit">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Import Page' , 'mediawiki-importer') ?>" />
					</p>

				</form>
			</div>
		<?php
	}

	function get_page_by_title() {
		$page_title = sanitize_text_field( $_POST['mw_pagetitle'] );
		$path = 'action=query&titles=' . $page_title . '&prop=revisions&rvparse=&rvprop=content';
		$url = $this->build_request_url( $path );
		$result = wp_remote_get( $url );
		$xml = simplexml_load_string($result['body']);
		var_dump($xml->query->pages->page->revisions->rev);
		wp_insert_post(
			array(
				'post_title' => $page_title,
				'post_content' => $xml->query->pages->page->revisions->rev
			)
		);
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

						<tr>
							<th scope="row"><label for="mw_siteurl"><?php _e( 'Mediawiki Site Url' , 'mediawiki-importer') ?></label></th>
							<td><input type="text" name="mw_siteurl" id="mw_siteurl" class="regular-text" /></td>
						</tr>

					</table>

					<p class="submit">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Connect to Mediawiki site and Import' , 'mediawiki-importer') ?>" />
					</p>

				</form>
			</div>
		<?php
	}

	function build_request_url( $path ) {
		$site_url = get_option('mw_import_siteurl');
		$url = $site_url . '?format=xml' . $path;
		return $url;
	}

	function setup() {

	}

	function dispatch() {
		if ( empty ( $_GET['step'] ) ) {
			$step = 0;
		}
		else {
			$this->setup(); // if no proper credentials is found redirect to login page
			$step = (int) $_GET['step'];
		}

		$this->header();

		switch ($step) {
			case 0 :
				$this->greet();
				break;
			case 1 :
				$this->display_menu();
				break;
			case 2 :
				$this->display_get_page_by_title();
				break;
			case 3 :
				$this->get_page_by_title();
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