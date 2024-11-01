<?php
/**
 * Fired during plugin updates
 *
 * @link       https://shapedplugin.com/
 */

// don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin updates.
 *
 * This class defines all code necessary to run during the plugin's updates.
 */
class Styble_Updates {

	/**
	 * DB updates that need to be run
	 *
	 * @var array
	 */
	private static $updates = array(
		'1.0.0' => 'updates/update-1.0.0.php',
	);

	/**
	 * Binding all events
	 *
	 * @return void
	 */
	public function __construct() {
		$this->do_updates();
	}

	/**
	 * Check if need any update
	 *
	 * @return boolean
	 */
	public function is_needs_update() {
		$installed_version = get_option( 'styble_version' );
		$first_version     = get_option( 'styble_first_version' );
		$activation_date   = get_option( 'styble_activation_date' );

		if ( false === $installed_version ) {
			update_option( 'styble_version', STYBLE_VERSION );
			update_option( 'styble_db_version', STYBLE_VERSION );
		}
		if ( false === $first_version ) {
			update_option( 'styble_first_version', STYBLE_VERSION );
		}
		if ( false === $activation_date ) {
			update_option( 'styble_activation_date', current_time( 'timestamp' ) );
		}

		if ( version_compare( $installed_version, STYBLE_VERSION, '<' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Do updates.
	 *
	 * @return void
	 */
	public function do_updates() {
		$this->perform_updates();
	}

	/**
	 * Perform all updates
	 *
	 * @return void
	 */
	public function perform_updates() {
		if ( ! $this->is_needs_update() ) {
			return;
		}

		$installed_version = get_option( 'styble_version' );

		foreach ( self::$updates as $version => $path ) {
			if ( version_compare( $installed_version, $version, '<' ) ) {
				include $path;
				update_option( 'styble_version', $version );
			}
		}
		update_option( 'styble_version', STYBLE_VERSION );
	}
}
new Styble_Updates();
