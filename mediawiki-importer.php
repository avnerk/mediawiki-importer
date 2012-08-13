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
	private $cookies = array();
	private $user_agent = 'WordPress Mediawiki Importer';
	private $timeout = 60;

	function Mediawiki_Import() {
		$this->site_url = get_option( 'mw_import_siteurl' );
		$cookies = get_transient( 'mw_import_cookie' );
		if( $cookies )
			$this->cookie = $cookies;
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

	function header() {
		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>'.__('Import Mediawiki', 'mediawiki-importer').'</h2>';
	}

	function footer() {
		echo '</div>';
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
							<td><input type="text" name="mw_username" id="mw_username" class="regular-text"  value="<?php echo esc_html( get_option( 'mw_import_username' ) ); ?>"/></td>
						</tr>

						<tr>
							<th scope="row"><label for="mw_password"><?php _e( 'Mediawiki Password' , 'mediawiki-importer') ?></label></th>
							<td><input type="password" name="mw_password" id="mw_password" class="regular-text"  value="<?php echo esc_html( $this->mw_import_decrypt( get_option( 'mw_import_siteurl' ) ) ); ?>"/></td>
						</tr>

						<tr>
							<th scope="row"><label for="mw_siteurl"><?php _e( 'Mediawiki Site Url' , 'mediawiki-importer') ?></label></th>
							<td><input type="text" name="mw_siteurl" id="mw_siteurl" class="regular-text" value="<?php echo esc_html( get_option( 'mw_import_siteurl' ) ); ?>"/></td>
						</tr>

					</table>

					<p class="submit">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Connect to Mediawiki site and Import' , 'mediawiki-importer') ?>" />
					</p>

				</form>
			</div>
		<?php
	}

	function display_menu() {

		$lgname = sanitize_text_field( $_POST['mw_username'] );
		update_option( 'mw_import_username', $lgname );
		$lgpassword = sanitize_text_field( $_POST['mw_password'] );
		update_option( 'mw_import_password', $this->mw_import_encrypt( $lgpassword ) );
		$siteurl = sanitize_text_field( $_POST['mw_siteurl'] );
		update_option( 'mw_import_siteurl', $siteurl );

		$result = $this->login();
		if ( is_wp_error( $result ) ) {
			//@TODO print a message login unsuccessful
		}

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
		$path = '&action=query&titles=' . urlencode( $page_title ) . '&prop=revisions&rvparse=&rvprop=content';
		$url = $this->build_request_url( $path );

		$response = wp_remote_get( $url );
		$result = $this->validate_response( $response );
		if( is_wp_error( $result ) ) {
			// handle error
		}

		wp_insert_post(
			array(
				'post_title' => $page_title,
				'post_content' => $result->query->pages->page->revisions->rev
			)
		);

	}

	// helper functions
	function setup() {

	}

	function login() {

		$lgname = get_option( 'mw_import_username' );
		if( empty( $lgname ) )
			return new WP_Error( 'mw_login', __( 'Empty username', 'mediawiki-importer') );

		$lgpassword = $this->mw_import_decrypt( get_option( 'mw_import_password' ) );
		if( empty( $lgname ) )
			return new WP_Error( 'mw_login', __( 'Empty password', 'mediawiki-importer') );

		$siteurl = get_option( 'mw_import_siteurl' );
		// @TODO validate siteurl

		// Send the request.
		$path = $siteurl . '/api.php?format=xml&action=login&lgname=' . $lgname . '&lgpassword=' . $lgpassword;
		$response = wp_remote_post(
			$path,
			array (
				'timeout' => $this->timeout,
				'user-agent' => $this->user_agent,
				'blocking' => true,
				'sslverify' => false
			)
		);

		if ( is_wp_error( $response ) )
			return new WP_Error( 'mw_login', __( $response->errors, 'mediawiki-importer') );

		// obtain the cookies returned
		$cookies = $response['cookies'];

		// obtain the login token returned
		$lgtoken = simplexml_load_string( $response['body'] )->login['token'];
		$path = $siteurl . '/api.php?format=xml&action=login&lgname=' . $lgname . '&lgpassword=' . $lgpassword . '&lgtoken=' . $lgtoken;

		// Request with login token.
		$response = wp_remote_post(
			$path,
			array (
				'timeout' => $this->timeout,
				'user-agent' => $this->user_agent,
				'blocking' => true,
				'cookies' => $cookies,
				'sslverify' => false
			)
		);

		if ( is_wp_error( $response ) )
			return new WP_Error( 'mediawiki_login', __( $response['body']['warnings']['info'], 'mediawiki-importer') );

		$cookies = $response['cookies'];
		set_transient( 'mw_import_cookie', $cookies, 60*60*24 );

	}

	function mw_import_encrypt( $data ) {
		$data = serialize( $data );
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5(MW_IMPORT_KEY), $data, MCRYPT_MODE_CBC, md5(md5(MW_IMPORT_KEY))));
	}

	function mw_import_decrypt( $data ) {
		$data = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5(MW_IMPORT_KEY), base64_decode($data), MCRYPT_MODE_CBC, md5(md5(MW_IMPORT_KEY))), "\0");
		if ( !$data )
			return false;

		return @unserialize( $data );
	}

	function build_request_url( $path ) {
		$site_url = get_option( 'mw_import_siteurl' );
		$url = $site_url . '/api.php?format=xml' . $path;
		return $url;
	}

}

$mediawiki_import = new Mediawiki_Import();

register_importer( 'mediawiki', __( 'Mediawiki', 'mediawiki-importer' ), __( 'Import content from Mediawiki sites.', 'mediawiki-importer' ), array( $mediawiki_import, 'dispatch' ) );

}